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
                        ->label(__('core::common.event'))
                        ->placeholder('—'),
                    TextEntry::make('description')
                        ->label(__('core::common.description'))
                        ->columnSpanFull(),
                    TextEntry::make('subject_type')
                        ->label(__('core::common.subject_type')),
                    TextEntry::make('subject_id')
                        ->label(__('core::common.subject_id')),
                    TextEntry::make('causer_type')
                        ->label(__('core::audit.causer_type')),
                    TextEntry::make('causer_id')
                        ->label(__('core::audit.causer_id')),
                    TextEntry::make('created_at')
                        ->label(__('core::common.created_at'))
                        ->dateTime(),
                    KeyValueEntry::make('attribute_changes')
                        ->label(__('core::audit.attribute_changes'))
                        ->state(fn (Activity $record): array => ActivityEntryPresenter::flattenChanges($record->attribute_changes))
                        ->columnSpanFull(),
                    KeyValueEntry::make('properties')
                        ->label(__('core::common.properties'))
                        ->state(fn (Activity $record): array => ActivityEntryPresenter::flattenProperties($record->properties))
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
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('core::common.created_at'))
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
                    ->label(__('core::common.description'))
                    ->searchable()
                    ->limit(50),
                TextColumn::make('event')
                    ->label(__('core::common.event'))
                    ->toggleable(),
                TextColumn::make('subject_type')
                    ->label(__('core::common.subject_type'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(40),
                TextColumn::make('causer.name')
                    ->label(__('core::audit.causer_id'))
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
