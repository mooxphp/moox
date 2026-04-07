<?php

namespace Moox\Core\Support\Resources\Concerns;

use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Moox\Core\Models\Scope;
use Moox\Core\Services\ScopeAssignmentValidator;
use Moox\Core\Services\ScopeRegistry;
use Moox\Core\Support\Resources\ScopedResourceContext;
use Moox\Core\Support\Scopes\ScopeValue;

trait HasScopedChildResource
{
    public const ASSIGN_GLOBAL_SCOPE = '__global__';

    public static function canAssignScopes(): bool
    {
        return static::hasMultipleAssignableScopes();
    }

    public static function formatScopeForDisplay(?string $scope): string
    {
        if ($scope === null || $scope === '') {
            return 'Global';
        }

        $parsed = ScopeValue::parse($scope);
        if ($parsed === null) {
            return $scope;
        }

        $origin = static::humanizeScopeSegment($parsed->origin());
        $source = static::humanizeScopeSegment($parsed->source());
        $context = static::humanizeScopeSegment($parsed->context());
        $boundary = static::humanizeScopeSegment($parsed->boundary());

        return "{$origin} → {$source} → {$context} ({$boundary})";
    }

    protected static function humanizeScopeSegment(string $value): string
    {
        return Str::headline(Str::snake($value));
    }

    /**
     * @return array<string, string>
     */
    public static function getAssignableScopeOptionsForRecord(?Model $record = null): array
    {
        return static::getAssignableScopeOptions();
    }

    public static function getDefaultAssignableScopeForRecord(?Model $record = null): string
    {
        if ($record && is_string($record->scope) && $record->scope !== '') {
            $options = static::getAssignableScopeOptions();
            if (array_key_exists($record->scope, $options)) {
                return $record->scope;
            }
        }

        if ($record && ($record->scope === null || $record->scope === '')) {
            return static::ASSIGN_GLOBAL_SCOPE;
        }

        return static::getDefaultAssignableScope();
    }

    /**
     * @return array{updated: bool, message?: string}
     */
    public static function assignScopeToRecord(Model $record, string $selectedScope): array
    {
        if (! static::recordSupportsScopeColumn($record)) {
            return ['updated' => false, 'message' => 'This record is not scopable.'];
        }

        $actor = Auth::user();
        $validator = app(ScopeAssignmentValidator::class);

        $targetScope = $selectedScope === static::ASSIGN_GLOBAL_SCOPE ? '' : $selectedScope;

        $validation = $validator->validate($record, $targetScope, $actor);
        if (! ($validation['allowed'] ?? false)) {
            return ['updated' => false, 'message' => 'Target scope was inactive or boundary rules were not fulfilled.'];
        }

        if ($selectedScope === static::ASSIGN_GLOBAL_SCOPE) {
            $record->setAttribute('scope', null);
        } else {
            $record->setAttribute('scope', ScopeValue::toStringOrNull($selectedScope));
        }

        $record->save();

        return ['updated' => true];
    }

    public static function getScopeSelectField(string $name = 'scope'): Select
    {
        return Select::make($name)
            ->label('Scope')
            ->dehydrated(false)
            ->live()
            ->options(fn (?Model $record) => static::getAssignableScopeOptionsForRecord($record))
            ->default(fn (?Model $record) => static::getDefaultAssignableScopeForRecord($record))
            ->afterStateUpdated(function ($state, ?Model $record, Select $component): void {
                if (! $record) {
                    return;
                }

                $result = static::assignScopeToRecord($record, (string) $state);

                if (! ($result['updated'] ?? false)) {
                    Notification::make()
                        ->warning()
                        ->title('Scope not allowed')
                        ->body($result['message'] ?? 'Unable to update scope.')
                        ->send();

                    $record->refresh();
                    $component->state(static::getDefaultAssignableScopeForRecord($record));

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Scope updated')
                    ->send();
            });
    }

    public static function scopeQuery(Builder $query): Builder
    {
        return ScopedResourceContext::applyScope($query, static::class);
    }

    public static function applyScopedDefaults(Model $record): void
    {
        ScopedResourceContext::applyDefaults($record, static::class);
    }

    protected static function getScopedDefinitionValue(string $definitionKey, mixed $default = null): mixed
    {
        return ScopedResourceContext::getDefinitionValue(static::class, $definitionKey) ?? value($default);
    }

    protected static function resolveScopedNavigationLabel(string $default): string
    {
        return (string) static::getScopedDefinitionValue('navigation_label', $default);
    }

    protected static function resolveScopedNavigationGroup(?string $default = null): ?string
    {
        return static::getScopedDefinitionValue('navigation_group', $default);
    }

    protected static function resolveScopedNavigationParentItem(?string $default = null): ?string
    {
        return static::getScopedDefinitionValue('navigation_parent_item', $default);
    }

    protected static function resolveScopedNavigationRegistration(bool $default = false): bool
    {
        return (bool) static::getScopedDefinitionValue('should_register_navigation', $default);
    }

    protected static function resolveScopedNavigationSort(?int $default = null): ?int
    {
        $sort = static::getScopedDefinitionValue('sort', $default);

        return $sort === null ? null : (int) $sort;
    }

    protected static function resolveScopedNavigationBadge(?Builder $query = null): ?string
    {
        $query ??= static::getModel()::query();

        return (string) static::scopeQuery($query)->count();
    }

    public static function getAssignScopeBulkAction(string $name = 'assignScope'): BulkAction
    {
        return BulkAction::make($name)
            ->label('Assign scope')
            ->icon('heroicon-m-adjustments-horizontal')
            ->visible(fn () => static::hasMultipleAssignableScopes())
            ->form([
                Select::make('scope')
                    ->label('Scope')
                    ->required()
                    ->default(fn () => static::getDefaultAssignableScope())
                    ->options(static::getAssignableScopeOptions()),
            ])
            ->action(function (Collection $records, array $data): void {
                $selectedScope = (string) ($data['scope'] ?? '');
                $actor = Auth::user();
                $validator = app(ScopeAssignmentValidator::class);
                $updatedCount = 0;
                $skippedCount = 0;

                foreach ($records as $record) {
                    if (! static::recordSupportsScopeColumn($record)) {
                        $skippedCount++;

                        continue;
                    }

                    $targetScope = $selectedScope === static::ASSIGN_GLOBAL_SCOPE
                        // Global is "unassigned" => we validate an empty target scope.
                        ? ''
                        : $selectedScope;

                    $validation = $validator->validate($record, $targetScope, $actor);
                    if (! ($validation['allowed'] ?? false)) {
                        $skippedCount++;

                        continue;
                    }

                    if ($selectedScope === static::ASSIGN_GLOBAL_SCOPE) {
                        $record->setAttribute('scope', null);
                    } else {
                        $record->setAttribute('scope', ScopeValue::toStringOrNull($selectedScope));
                    }

                    $record->save();
                    $updatedCount++;
                }

                if ($updatedCount > 0) {
                    Notification::make()
                        ->success()
                        ->title("Scope updated for {$updatedCount} record(s)")
                        ->send();
                }

                if ($skippedCount > 0) {
                    Notification::make()
                        ->warning()
                        ->title("Skipped {$skippedCount} record(s)")
                        ->body('Target scope was inactive or boundary rules were not fulfilled.')
                        ->send();
                }
            });
    }

    /**
     * @return array<string, string>
     */
    protected static function getAssignableScopeOptions(): array
    {
        $options = [
            static::ASSIGN_GLOBAL_SCOPE => 'Global',
        ];

        if (! Schema::hasTable('scopes')) {
            return $options;
        }

        $origin = static::resolveScopeOriginFromResource();

        if (blank($origin)) {
            return $options;
        }

        /** @var array<string, array{scope: string, label: string|null, source: string, context: string, boundary: string}> $rows */
        $rows = Scope::query()
            ->where('origin', $origin)
            ->where('is_active', true)
            ->orderByRaw('label is null, label asc, scope asc')
            ->get(['scope', 'label', 'source', 'context', 'boundary'])
            ->mapWithKeys(fn ($row) => [(string) $row->scope => [
                'scope' => (string) $row->scope,
                'label' => $row->label,
                'source' => (string) $row->source,
                'context' => (string) $row->context,
                'boundary' => (string) $row->boundary,
            ]])
            ->toArray();

        foreach ($rows as $scope => $row) {
            $parsed = ScopeValue::parse($scope);
            if ($parsed === null) {
                $options[$scope] = $scope;

                continue;
            }

            $originLabel = static::humanizeScopeSegment($parsed->origin());
            $sourceLabel = static::humanizeScopeSegment($parsed->source());
            $contextLabel = static::humanizeScopeSegment($parsed->context());
            $boundaryLabel = static::humanizeScopeSegment($parsed->boundary());

            $options[$scope] = "{$originLabel} → {$sourceLabel} → {$contextLabel} ({$boundaryLabel})";
        }

        return $options;
    }

    protected static function resolveScopeOriginFromResource(): ?string
    {
        $scope = ScopedResourceContext::getParsedScope(static::class);
        if ($scope !== null) {
            return $scope->origin();
        }

        $model = static::getModel();

        return app(ScopeRegistry::class)->resolveOriginKeyForModel($model);
    }

    protected static function getDefaultAssignableScope(): string
    {
        $currentScope = ScopedResourceContext::getScope(static::class);
        $options = static::getAssignableScopeOptions();

        if (is_string($currentScope) && array_key_exists($currentScope, $options)) {
            return $currentScope;
        }

        return static::ASSIGN_GLOBAL_SCOPE;
    }

    protected static function hasMultipleAssignableScopes(): bool
    {
        $options = static::getAssignableScopeOptions();

        return count($options) > 1;
    }

    protected static function recordSupportsScopeColumn(Model $record): bool
    {
        return Schema::hasColumn($record->getTable(), 'scope');
    }
}
