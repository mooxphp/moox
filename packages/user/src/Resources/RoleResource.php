<?php declare(strict_types=1);

namespace Moox\User\Resources;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Moox\User\Fields\PermissionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Moox\User\Traits\HasExtendableSchema;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Moox\User\Resources\RoleResource\Pages\EditRole;
use Moox\User\Resources\RoleResource\Pages\ListRoles;
use Moox\User\Resources\RoleResource\Pages\CreateRole;

class RoleResource extends Resource
{
    use HasExtendableSchema;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function getModel(): string
    {
        return config('permission.models.role');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                ...static::insertBeforeFormSchema(),
                TextInput::make('name')
                    ->label(__('user::translation.fields.name'))
                    ->validationAttribute(__('user::translation.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->unique(config('permission.table_names.roles'), 'name', static fn ($record) => $record),
                PermissionGroup::make('permissions')
                    ->label(__('user::translation.fields.permissions'))
                    ->validationAttribute(__('user::translation.fields.permissions')),
                ...static::insertAfterFormSchema(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ...static::insertBeforeTableSchema(),
                TextColumn::make('id')
                    ->label(__('user::translation.fields.id'))
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('user::translation.fields.description'))
                    ->getStateUsing(static fn ($record) => __($record->name)),
                TextColumn::make('name')
                    ->label(__('user::translation.fields.name'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('user::translation.fields.created_at'))
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
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): string
    {
        return __('Role');
    }

    public static function getPluralLabel(): string
    {
        return __('Roles');
    }

    public static function getEloquentQuery(): Builder
    {
        $model = RoleResource::class;

        return $model::query()->where('guard_name', '=', config('guard_name'));
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Moox User');
    }
}
