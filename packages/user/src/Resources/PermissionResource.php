<?php

declare(strict_types=1);

namespace Moox\User\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\User\Resources\PermissionResource\Pages\CreatePermission;
use Moox\User\Resources\PermissionResource\Pages\EditPermission;
use Moox\User\Resources\PermissionResource\Pages\ListPermissions;
use Moox\User\Traits\HasExtendableSchema;
use Spatie\Permission\Models\Permission;

class PermissionResource extends Resource
{
    use HasExtendableSchema;

    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    public static function getModel(): string
    {
        return PermissionResource::class;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ...static::insertBeforeFormSchema(),
                TextInput::make('name')
                    ->label(__('user::translations.fields.name'))
                    ->validationAttribute(__('user::translations.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        config('permission.table_names.permissions'),
                        'name',
                        static fn (?Permission $record): ?Permission => $record,
                    ),
                ...static::insertAfterFormSchema(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ...static::insertBeforeTableSchema(),
                TextColumn::make('id')
                    ->label(__('user::translations.fields.id'))
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('user::translations.fields.description'))
                    ->getStateUsing(static fn (Permission $record) => __($record->name)),
                TextColumn::make('name')
                    ->label(__('user::translations.fields.name'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('user::translations.fields.created_at'))
                    ->dateTime(),
                ...static::insertAfterTableSchema(),
            ])
            ->actions([EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->filters([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
            'create' => CreatePermission::route('/create'),
            'edit' => EditPermission::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): string
    {
        return __('Permission');
    }

    public static function getPluralLabel(): string
    {
        return __('user::translations.resources.permissions');
    }

    public static function getEloquentQuery(): Builder
    {
        return Permission::query()->where('guard_name', '=', config('user.guard_name'));
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Moox User');
    }
}
