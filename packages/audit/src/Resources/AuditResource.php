<?php

declare(strict_types=1);

namespace Moox\Audit\Resources;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Audit\Models\Activity;
use Moox\Audit\Resources\AuditResource\Pages\ListAudits;
use Moox\Audit\Resources\AuditResource\Pages\ViewAudit;
use Moox\Audit\Support\ActivityEntryPresenter;
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
        return $schema->components([
            Section::make()->schema([
                Grid::make(['default' => 2])->schema([
                    TextEntry::make('log_name')
                        ->label(__('core::audit.log_name')),
                    TextEntry::make('entry_type')
                        ->label(__('core::audit.entry_type'))
                        ->badge(),
                    TextEntry::make('scope')
                        ->label(__('core::audit.scope'))
                        ->placeholder('—'),
                    TextEntry::make('event')
                        ->label(__('core::core.event'))
                        ->placeholder('—'),
                    TextEntry::make('description')
                        ->label(__('core::core.description'))
                        ->columnSpanFull(),
                    TextEntry::make('subject_label')
                        ->label(__('core::audit.subject'))
                        ->state(fn (Activity $record): string => ActivityEntryPresenter::subjectLabel($record))
                        ->columnSpanFull(),
                    TextEntry::make('causer_label')
                        ->label(__('core::audit.causer'))
                        ->state(fn (Activity $record): string => ActivityEntryPresenter::causerLabel($record)),
                    TextEntry::make('created_at')
                        ->label(__('core::core.created_at'))
                        ->dateTime(),
                    KeyValueEntry::make('attribute_changes')
                        ->label(__('core::audit.attribute_changes'))
                        ->state(fn (Activity $record): array => ActivityEntryPresenter::flattenChanges($record->attribute_changes))
                        ->visible(fn (Activity $record): bool => ActivityEntryPresenter::flattenChanges($record->attribute_changes) !== [])
                        ->columnSpanFull(),
                    KeyValueEntry::make('properties')
                        ->label(__('core::core.properties'))
                        ->state(fn (Activity $record): array => ActivityEntryPresenter::flattenProperties($record->properties))
                        ->visible(fn (Activity $record): bool => ActivityEntryPresenter::flattenProperties($record->properties) !== [])
                        ->columnSpanFull(),
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
                    ->label(__('core::core.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('entry_type')
                    ->label(__('core::audit.entry_type'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('log_name')
                    ->label(__('core::audit.log_name'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('scope')
                    ->label(__('core::audit.scope'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(40),
                TextColumn::make('description')
                    ->label(__('core::core.description'))
                    ->searchable()
                    ->limit(50),
                TextColumn::make('event')
                    ->label(__('core::core.event'))
                    ->toggleable(),
                TextColumn::make('subject_label')
                    ->label(__('core::audit.subject'))
                    ->state(fn (Activity $record): string => ActivityEntryPresenter::subjectLabel($record))
                    ->toggleable(isToggledHiddenByDefault: true)
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
