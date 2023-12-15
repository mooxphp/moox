<?php

namespace Moox\User\Resources;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Moox\User\Resources\UserResource\Pages\ListPage;
use Moox\User\UserPlugin;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function form(Form $form): Form
    {
        $rows = [
            TextInput::make('name')
                ->required()
                ->label(trans('user::translations.name')),
            TextInput::make('email')
                ->email()
                ->required()
                ->label(trans('user::translations.email')),
            TextInput::make('password')
                ->label(trans('user::translations.password'))
                ->password()
                ->maxLength(255)
                ->dehydrateStateUsing(static function ($state) use ($form) {
                    return ! empty($state)
                        ? Hash::make($state)
                        : User::find($form->getColumns())?->password;
                }),
        ];

        if (config('user.shield')) {
            $rows[] = Select::make('roles')
                ->multiple()
                ->preload()
                ->relationship('roles', 'name')
                ->label(trans('user::translations.roles'));
        }

        $form->schema($rows);

        return $form;
    }

    public static function table(Table $table): Table
    {
        ! config('user.impersonate') ?: $table->actions([Impersonate::make('impersonate')]);
        $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->label(trans('user::translations.id')),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label(trans('user::translations.name')),
                TextColumn::make('email')
                    ->sortable()
                    ->searchable()
                    ->label(trans('user::translations.email')),
                IconColumn::make('email_verified_at')
                    ->boolean()
                    ->sortable()
                    ->searchable()
                    ->label(trans('user::translations.email_verified_at')),
                TextColumn::make('created_at')
                    ->label(trans('user::translations.created_at'))
                    ->dateTime('M j, Y')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(trans('user::translations.updated_at'))
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('verified')
                    ->label(trans('user::translations.verified'))
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at')),
                Filter::make('unverified')
                    ->label(trans('user::translations.unverified'))
                    ->query(fn (Builder $query): Builder => $query->whereNull('email_verified_at')),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);

        return $table;
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

    public static function getNavigationBadge(): ?string
    {
        return UserPlugin::make()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function getModelLabel(): string
    {
        return UserPlugin::make()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return UserPlugin::make()->getPluralLabel();
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        return UserPlugin::make()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return UserPlugin::make()->getNavigationSort();
    }

    public static function getBreadcrumb(): string
    {
        return UserPlugin::make()->getBreadcrumb();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return UserPlugin::make()->shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): string
    {
        return UserPlugin::make()->getNavigationIcon();
    }
}
