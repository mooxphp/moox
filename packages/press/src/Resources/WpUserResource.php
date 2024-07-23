<?php

namespace Moox\Press\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use Moox\Press\Models\WpUser;
use Moox\Press\Resources\WpUserResource\Pages\CreateWpUser;
use Moox\Press\Resources\WpUserResource\Pages\EditWpUser;
use Moox\Press\Resources\WpUserResource\Pages\ListWpUsers;
use Moox\Press\Resources\WpUserResource\Pages\ViewWpUser;
use Moox\Press\Resources\WpUserResource\RelationManagers\WpUserMetaRelationManager;
use Moox\Security\Helper\PasswordHash;

class WpUserResource extends Resource
{
    protected static ?string $model = WpUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $recordTitleAttribute = 'user_login';

    public static function getModelLabel(): string
    {
        return 'User';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Users';
    }

    public static function getNavigationLabel(): string
    {
        return 'Users';
    }

    protected static ?string $navigationGroup = 'Moox Press Admin';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('user_login')
                        ->rules(['max:60', 'string'])
                        ->required()
                        ->placeholder('User Login')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('user_nicename')
                        ->rules(['max:50', 'string'])
                        ->required()
                        ->placeholder('User Nicename')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('user_email')
                        ->rules(['max:100', 'string'])
                        ->required()
                        ->placeholder('User Email')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('user_url')
                        ->rules(['max:100', 'string'])
                        ->required()
                        ->placeholder('User Url')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('user_registered')
                        ->rules(['date'])
                        ->required()
                        ->placeholder('User Registered')
                        ->default('0000-00-00 00:00:00')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('user_activation_key')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('User Activation Key')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('user_status')
                        ->rules(['numeric'])
                        ->required()
                        ->numeric()
                        ->placeholder('User Status')
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('first_name')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('First Name')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('last_name')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Last Name')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('user_pass')
                        ->revealable()
                        ->label('Password')
                        ->password()
                        ->visibleOn('create')
                        ->dehydrateStateUsing(function ($state) {
                            $passwordHash = new PasswordHash(8, true);

                            return $passwordHash->HashPassword($state);
                        })
                        ->rule(Password::min(8))
                        ->required()
                        ->placeholder('Password')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('password_confirmation')
                        ->requiredWith('user_pass')
                        ->password()
                        ->same('user_pass')
                        ->visibleOn('create')
                        ->placeholder('Password Confirmation')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                ]),
            ]),

            Section::make('Update Password')->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('current_password')
                        ->revealable()
                        ->password()
                        ->rule('current_password')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),
                    TextInput::make('new_password')
                        ->revealable()
                        ->password()
                        ->rule(Password::min(8))
                        // ->helperText('Your password must be at least 8 characters long and contain a mix of uppercase and lowercase letters, numbers, and symbols.')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),
                    TextInput::make('new_password_confirmation')
                        ->password()
                        ->label('Confirm new password')
                        ->same('new_password')
                        ->requiredWith('new_password')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                ]),
            ])->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                Tables\Columns\TextColumn::make('user_login')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('user_pass')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('user_nicename')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('user_email')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('user_url')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('user_registered')
                    ->toggleable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('user_activation_key')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\TextColumn::make('user_status')
                    ->toggleable()
                    ->searchable(true, null, true),
                Tables\Columns\TextColumn::make('display_name')
                    ->toggleable()
                    ->searchable(true, null, true)
                    ->limit(50),
                Tables\Columns\IconColumn::make('spam')
                    ->toggleable()
                    ->boolean(),
                Tables\Columns\IconColumn::make('deleted')
                    ->toggleable()
                    ->boolean(),
            ])
            ->filters([])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            WpUserMetaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWpUsers::route('/'),
            'create' => CreateWpUser::route('/create'),
            'view' => ViewWpUser::route('/{record}'),
            'edit' => EditWpUser::route('/{record}/edit'),
        ];
    }
}
