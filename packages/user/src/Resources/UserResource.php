<?php

namespace Moox\User\Resources;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Moox\Security\FilamentActions\Passwords\SendPasswordResetLinksBulkAction;
use Moox\User\Models\User;
use Moox\User\Resources\UserResource\Pages\CreateUser;
use Moox\User\Resources\UserResource\Pages\EditUser;
use Moox\User\Resources\UserResource\Pages\ListUsers;
use Moox\User\Resources\UserResource\Pages\ViewUser;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'gmdi-manage-accounts';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    FileUpload::make('profile_photo_path')
                        ->avatar()
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    FileUpload::make('avatar_url'),

                    TextInput::make('name')
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->placeholder('Name')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('slug')
                        ->rules(['max:255', 'string'])
                        ->placeholder('Slug')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Select::make('roles')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Select::make('gender')
                        ->rules(['in:unknown,male,female,other'])
                        ->required()
                        ->searchable()
                        ->options([
                            'unknown' => 'Unknown',
                            'female' => 'Female',
                            'male' => 'Male',
                            'other' => 'Other',
                        ])
                        ->placeholder('Gender')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('title')
                        ->rules(['max:255', 'string'])
                        ->nullable()
                        ->placeholder('Title')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('first_name')
                        ->rules(['max:255', 'string'])
                        ->placeholder('First Name')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('last_name')
                        ->rules(['max:255', 'string'])
                        ->placeholder('Last Name')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('email')
                        ->rules(['email'])
                        ->required()
                        ->unique(
                            'users',
                            'email',
                            fn (?Model $record) => $record
                        )
                        ->email()
                        ->placeholder('Email')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('website')
                        ->rules(['max:255', 'string'])
                        ->nullable()
                        ->placeholder('Website')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('description')
                        ->rules(['max:255', 'string'])
                        ->placeholder('Description')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('password')
                        ->revealable()
                        ->required()
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->password()
                        ->visibleOn('create')
                        ->rule(Password::min(8)->mixedCase()->numbers()->symbols())
                        ->helperText('Your password must be at least 8 characters long and contain a mix of uppercase and lowercase letters, numbers, and symbols.')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('password_confirmation')
                        ->requiredWith('password')
                        ->password()
                        ->same('password')
                        ->visibleOn('create')
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
                        ->rule(Password::min(8)->mixedCase()->numbers()->symbols())
                        ->helperText('Your password must be at least 8 characters long and contain a mix of uppercase and lowercase letters, numbers, and symbols.')
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
                ImageColumn::make('profile_photo_path')
                    ->defaultImageUrl(fn ($record): string => 'https://ui-avatars.com/api/?name='.$record->name)
                    ->circular()
                    ->label(__('Avatar'))
                    ->toggleable(),
                TextColumn::make('name')
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('last_name')
                    ->label(__('Fullname'))
                    ->formatStateUsing(function ($state, User $user) {
                        return $user->first_name.' '.$user->last_name;
                    })
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('email')
                    ->label('Email')
                    ->alignEnd()
                    ->sortable()
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->sortable()
                    ->alignStart()
                    ->icon(
                        fn ($record): string => is_null(
                            $record->email_verified_at) ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle'
                    )
                    ->colors([
                        'success' => fn ($record) => $record->email_verified_at !== null,
                        'danger' => fn ($record) => $record->email_verified_at === null,
                    ]),
                IconColumn::make('roles.name')
                    ->label(__('Admin'))
                    ->sortable()
                    ->alignCenter()
                    ->icons([
                        'heroicon-o-shield-exclamation' => fn ($record) => $record->roles->pluck('name')->contains('super_admin'),
                    ])
                    ->colors([
                        'warning' => fn ($record) => $record->roles->pluck('name')->contains('super_admin'),
                    ]),
            ])
            ->filters([
                SelectFilter::make('language_id')
                    ->relationship('language', 'title')
                    ->indicator('Language')
                    ->multiple()
                    ->label('Language'),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions(array_filter([
                DeleteBulkAction::make(),
                (config('security.actions.bulkactions.sendPasswordResetLinkBulkAction')) ?
                SendPasswordResetLinksBulkAction::make() : null,
            ]));
    }

    public static function getRelations(): array
    {
        return [
            //UserResource\RelationManagers\AuthorsRelationManager::class,
            //UserResource\RelationManagers\SessionsRelationManager::class,
            //UserResource\RelationManagers\SyncsRelationManager::class,
            //UserResource\RelationManagers\PlatformsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('user::translations.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('user::translations.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('user::translations.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('user::translations.navigation_group');
    }

    public static function getBreadcrumb(): string
    {
        return __('user::translations.breadcrumb');
    }

    public static function getNavigationSort(): ?int
    {
        return config('user.navigation_sort');
    }
}
