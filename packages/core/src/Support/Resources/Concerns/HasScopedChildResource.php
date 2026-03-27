<?php

namespace Moox\Core\Support\Resources\Concerns;

use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Services\ScopeAssignmentValidator;
use Moox\Core\Services\ScopeRegistry;
use Moox\Core\Support\Resources\ScopedResourceContext;
use Moox\Core\Support\Scopes\ScopeValue;

trait HasScopedChildResource
{
    public const ASSIGN_GLOBAL_SCOPE = '__global__';

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
                    if (! method_exists($record, 'assignScope')) {
                        $skippedCount++;
                        continue;
                    }

                    $targetScope = $selectedScope === static::ASSIGN_GLOBAL_SCOPE
                        ? ($record->resolveGlobalScopeString() ?? '')
                        : $selectedScope;

                    $validation = $validator->validate($record, $targetScope, $actor);
                    if (! ($validation['allowed'] ?? false)) {
                        $skippedCount++;
                        continue;
                    }

                    if ($selectedScope === static::ASSIGN_GLOBAL_SCOPE) {
                        $record->assignScope(null);
                    } else {
                        $record->assignScope($selectedScope);
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
        $rows = \Moox\Core\Models\Scope::query()
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
            $base = $row['label'] ?: $row['scope'];
            $options[$scope] = "{$base} — {$row['source']}/{$row['context']} ({$row['boundary']})";
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
}
