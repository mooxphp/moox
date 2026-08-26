<?php

declare(strict_types=1);

namespace Moox\Audit\Resources;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Moox\Audit\Models\Activity;
use Moox\Audit\Resources\AuditResource\Pages\ListAudits;
use Moox\Audit\Resources\AuditResource\Pages\ViewAudit;
use Moox\Audit\Support\ActivityEntryPresenter;
use Moox\Audit\Support\SubjectUrlResolver;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Override;

class AuditResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = Activity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-troubleshoot';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    #[Override]
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                            TextEntry::make('subject_label')
                                ->label(__('core::audit.subject'))
                                ->state(fn (Activity $record): string => ActivityEntryPresenter::subjectLabel($record))
                                ->url(fn (Activity $record): ?string => SubjectUrlResolver::forActivity($record))
                                ->openUrlInNewTab()
                                ->color(function (Activity $record): ?string {
                                    if (ActivityEntryPresenter::subjectIsUnavailable($record)) {
                                        return 'gray';
                                    }

                                    return SubjectUrlResolver::forActivity($record) ? 'primary' : null;
                                })
                                ->helperText(function (Activity $record): ?string {
                                    $unavailable = ActivityEntryPresenter::subjectUnavailableHint($record);

                                    if ($unavailable !== null) {
                                        return $unavailable;
                                    }

                                    $parts = array_filter([
                                        ActivityEntryPresenter::propertyValue($record, 'locale'),
                                        filled($record->scope) ? $record->scope : null,
                                    ]);

                                    return $parts === [] ? null : implode(' · ', $parts);
                                }),
                            TextEntry::make('causer_label')
                                ->label(__('core::audit.causer'))
                                ->state(fn (Activity $record): string => ActivityEntryPresenter::causerLabel($record)),
                            TextEntry::make('event')
                                ->label(__('core::audit.action'))
                                ->state(fn (Activity $record): string => ActivityEntryPresenter::eventLabel($record))
                                ->badge(),
                            TextEntry::make('created_at')
                                ->label(__('core::audit.occurred_at'))
                                ->dateTime(),
                        ]),
                        TextEntry::make('description')
                            ->label(__('core::core.description'))
                            ->visible(fn (Activity $record): bool => ActivityEntryPresenter::hasDistinctDescription($record))
                            ->columnSpanFull(),
                    ]),
                Section::make(__('core::audit.attribute_changes'))
                    ->schema([
                        TextEntry::make('no_attribute_changes')
                            ->hiddenLabel()
                            ->state(__('core::audit.no_changes'))
                            ->color('gray')
                            ->visible(fn (Activity $record): bool => ActivityEntryPresenter::changeRows($record->attribute_changes, $record) === []),
                        RepeatableEntry::make('attribute_changes')
                            ->hiddenLabel()
                            ->state(fn (Activity $record): array => ActivityEntryPresenter::changeRows($record->attribute_changes, $record))
                            ->visible(fn (Activity $record): bool => ActivityEntryPresenter::changeRows($record->attribute_changes, $record) !== [])
                            ->table([
                                TableColumn::make(__('core::audit.field')),
                                TableColumn::make(__('core::audit.change')),
                                TableColumn::make(__('core::audit.old_value')),
                                TableColumn::make(__('core::audit.new_value')),
                            ])
                            ->schema([
                                TextEntry::make('field')
                                    ->weight(FontWeight::Medium),
                                TextEntry::make('kind')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => $state
                                        ? __('core::audit.change_kind_'.$state)
                                        : '—')
                                    ->color(fn (?string $state): string => match ($state) {
                                        'added' => 'success',
                                        'removed' => 'danger',
                                        'changed' => 'warning',
                                        default => 'gray',
                                    }),
                                TextEntry::make('old')
                                    ->placeholder('—')
                                    ->fontFamily('mono')
                                    ->color(fn (?string $state): ?string => filled($state) ? 'danger' : null)
                                    ->limit(ActivityEntryPresenter::CHANGE_VALUE_DISPLAY_LIMIT)
                                    ->tooltip(fn (?string $state): ?string => ActivityEntryPresenter::truncatedChangeTooltip($state)),
                                TextEntry::make('new')
                                    ->placeholder('—')
                                    ->fontFamily('mono')
                                    ->color(fn (?string $state): ?string => filled($state) ? 'success' : null)
                                    ->limit(ActivityEntryPresenter::CHANGE_VALUE_DISPLAY_LIMIT)
                                    ->tooltip(fn (?string $state): ?string => ActivityEntryPresenter::truncatedChangeTooltip($state)),
                            ]),
                    ]),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['causer', 'subject']))
            ->recordClasses(fn (Activity $record): ?string => ActivityEntryPresenter::listRecordClasses($record->attribute_changes))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('core::audit.occurred_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('entry_type')
                    ->label(__('core::audit.entry_type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'log' => __('core::audit.entry_type_log'),
                        'audit' => __('core::audit.entry_type_audit'),
                        default => filled($state) ? Str::headline($state) : '—',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('log_name')
                    ->label(__('core::audit.log_name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('event')
                    ->label(__('core::audit.action'))
                    ->state(fn (Activity $record): string => ActivityEntryPresenter::eventLabel($record))
                    ->badge()
                    ->color(fn (Activity $record): string => ActivityEntryPresenter::isFailureEntry($record->attribute_changes) ? 'danger' : 'gray')
                    ->toggleable(),
                TextColumn::make('subject_label')
                    ->label(__('core::audit.subject'))
                    ->state(fn (Activity $record): string => ActivityEntryPresenter::subjectLabel($record))
                    ->limit(40),
                TextColumn::make('changed_fields')
                    ->label(__('core::audit.attribute_changes'))
                    ->state(fn (Activity $record): string => ActivityEntryPresenter::changedFieldsSummary($record->attribute_changes, activity: $record))
                    ->color(fn (Activity $record): ?string => ActivityEntryPresenter::isFailureEntry($record->attribute_changes) ? 'danger' : null)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('causer_label')
                    ->label(__('core::audit.causer'))
                    ->state(fn (Activity $record): string => ActivityEntryPresenter::causerLabel($record))
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('entry_type')
                    ->label(__('core::audit.entry_type'))
                    ->options([
                        'log' => __('core::audit.entry_type_log'),
                        'audit' => __('core::audit.entry_type_audit'),
                    ]),
                SelectFilter::make('event')
                    ->label(__('core::audit.action'))
                    ->options([
                        'created' => __('core::audit.event_created'),
                        'updated' => __('core::audit.event_updated'),
                        'deleted' => __('core::audit.event_deleted'),
                        'restored' => __('core::audit.event_restored'),
                    ]),
                SelectFilter::make('subject_type')
                    ->label(__('core::audit.subject'))
                    ->options(fn (): array => Activity::query()
                        ->whereNotNull('subject_type')
                        ->distinct()
                        ->orderBy('subject_type')
                        ->pluck('subject_type')
                        ->filter(fn (mixed $type): bool => is_string($type) && $type !== '')
                        ->mapWithKeys(fn (string $type): array => [
                            $type => ActivityEntryPresenter::subjectTypeLabel($type),
                        ])
                        ->all()),
                SelectFilter::make('log_name')
                    ->label(__('core::audit.log_name')),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListAudits::route('/'),
            'view' => ViewAudit::route('/{record}'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('audit.resources.audit.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('audit.resources.audit.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('audit.resources.audit.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('audit.resources.audit.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('audit.navigation_group');
    }
}
