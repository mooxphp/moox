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
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                        TextEntry::make('subject_label')
                            ->label(__('core::audit.subject'))
                            ->state(fn (Activity $record): string => ActivityEntryPresenter::subjectLabel($record))
                            ->url(fn (Activity $record): ?string => SubjectUrlResolver::forActivity($record))
                            ->openUrlInNewTab()
                            ->color(fn (Activity $record): ?string => SubjectUrlResolver::forActivity($record) ? 'primary' : null)
                            ->weight(FontWeight::SemiBold)
                            ->size(TextSize::Large)
                            ->icon(fn (Activity $record): ?string => SubjectUrlResolver::forActivity($record) ? 'heroicon-m-arrow-top-right-on-square' : null)
                            ->helperText(function (Activity $record): ?string {
                                $parts = array_filter([
                                    ActivityEntryPresenter::subjectIdLabel($record),
                                    ActivityEntryPresenter::propertyValue($record, 'locale'),
                                    filled($record->scope) ? $record->scope : null,
                                ]);

                                return $parts === [] ? null : implode(' · ', $parts);
                            }),
                        Grid::make(['default' => 1, 'md' => 3])->schema([
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
                        RepeatableEntry::make('attribute_changes')
                            ->label(__('core::audit.attribute_changes'))
                            ->state(fn (Activity $record): array => ActivityEntryPresenter::changeRows($record->attribute_changes))
                            ->visible(fn (Activity $record): bool => ActivityEntryPresenter::changeRows($record->attribute_changes) !== [])
                            ->table([
                                TableColumn::make(__('core::audit.field')),
                                TableColumn::make(__('core::audit.action')),
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
                                    ->fontFamily('mono'),
                                TextEntry::make('new')
                                    ->placeholder('—')
                                    ->fontFamily('mono'),
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
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('core::audit.occurred_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('entry_type')
                    ->label(__('core::audit.entry_type'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('log_name')
                    ->label(__('core::audit.log_name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('event')
                    ->label(__('core::audit.action'))
                    ->state(fn (Activity $record): string => ActivityEntryPresenter::eventLabel($record))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('subject_label')
                    ->label(__('core::audit.subject'))
                    ->state(fn (Activity $record): string => ActivityEntryPresenter::subjectLabel($record))
                    ->limit(40),
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
