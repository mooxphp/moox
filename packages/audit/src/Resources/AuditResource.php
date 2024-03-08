<?php

namespace Moox\Audit\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Audit\Resources\AuditResource\Pages\ListPage;
use Moox\Audit\Resources\AuditResource\Widgets\AuditWidgets;
use Spatie\Activitylog\Models\Activity;

class AuditResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->maxLength(255),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('finished_at'),
                Toggle::make('failed')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('audit::translations.name'))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('audit::translations.started_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('failed')
                    ->label(__('audit::translations.failed'))
                    ->sortable(),
            ])
            ->defaultSort('name', 'desc')
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPage::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            AuditWidgets::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return __('audit::translations.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('audit::translations.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('audit::translations.navigation_label');
    }

    public static function getBreadcrumb(): string
    {
        return __('audit::translations.breadcrumb');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count());
    }

    public static function getNavigationGroup(): ?string
    {
        return __('audit::translations.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return 2001;
    }
}
