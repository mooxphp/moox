<?php

namespace Moox\Audit\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Audit\Resources\AuditResource\Pages\ListAudits;
use Moox\Audit\Resources\AuditResource\Pages\ViewAudit;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Override;
use Spatie\Activitylog\Models\Activity;

class AuditResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = Activity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-troubleshoot';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('log_name')
                        ->label(__('core:audit.log_name'))
                        ->rules(['max:255', 'string'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('description')
                        ->label(__('core:common.description'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('subject_type')
                        ->label(__('core:common.subject_type'))
                        ->rules(['max:255', 'string'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('event')
                        ->label(__('core:common.event'))
                        ->rules(['max:255', 'string'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('subject_id')
                        ->label(__('core:common.subject_id'))
                        ->rules(['max:255'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('causer_type')
                        ->label(__('core:audit.causer_type'))
                        ->rules(['max:255', 'string'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('causer_id')
                        ->label(__('core:audit.causer_id'))
                        ->rules(['max:255'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    KeyValue::make('properties')
                        ->label(__('core:common.properties'))
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('batch_uuid')
                        ->label(__('core:audit.batch_uuid'))
                        ->rules(['max:255'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),
                ]),
            ]),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('log_name')
                    ->label(__('core:audit.log_name'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('description')
                    ->label(__('core:common.description'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('subject_type')
                    ->label(__('core:common.subject_type'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('event')
                    ->label(__('core:common.event'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('subject_id')
                    ->label(__('core:common.subject_id'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('causer_type')
                    ->label(__('core:audit.causer_type'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('causer_id')
                    ->label(__('core:audit.causer_id'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                TextColumn::make('batch_uuid')
                    ->label(__('core:audit.batch_uuid'))
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
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
