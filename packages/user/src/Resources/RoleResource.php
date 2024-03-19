<?php declare(strict_types=1);

namespace Moox\User\Resources;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
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
use Moox\User\Fields\PermissionGroup;

class RoleResource extends Resource
{
    use HasExtendableSchema;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function getModel(): string
    {
        return RoleResource::class;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                ...static::insertBeforeFormSchema(),
                TextInput::make('name')
                    ->label(__('user::translations.fields.name'))
                    ->validationAttribute(__('user::translations.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->unique(config('permission.table_names.roles'), 'name', static fn ($record) => $record),
                PermissionGroup::make('permissions')
                    ->label(__('user::translations.fields.permissions'))
                    ->validationAttribute(__('user::translationsfields.permissions')),
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
                    ->getStateUsing(static fn ($record) => __($record->name)),
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
        $model = PermissionResource::class;

        return $model::query()->where('guard_name', '=', config('user.guard_name'));
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Moox User');
    }
}
