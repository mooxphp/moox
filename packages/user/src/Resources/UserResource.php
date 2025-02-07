<?php

namespace Moox\User\Resources;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Moox\User\Models\User;
use Moox\Sync\Models\Platform;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Livewire;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Validation\Rules\Password;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\Media\Forms\Components\MediaPicker;
use Filament\Tables\Actions\DeleteBulkAction;
use Moox\User\Resources\UserResource\Pages\EditUser;
use Moox\User\Resources\UserResource\Pages\ViewUser;
use Moox\User\Resources\UserResource\Pages\ListUsers;
use Moox\User\Resources\UserResource\Pages\CreateUser;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Moox\Security\FilamentActions\Passwords\SendPasswordResetLinksBulkAction;

class UserResource extends Resource
{
    use BaseInResource;
    use TabsInResource;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'gmdi-manage-accounts';

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([

                    MediaPicker::make('avatar_url')
                        ->label('Avatar')
                        ->multiple(),

                    TextInput::make('name')
                        ->label(__('core::core.name'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('slug')
                        ->label(__('core::core.slug'))
                        ->rules(['max:255', 'string'])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Select::make('roles')
                        ->label(__('core::user.roles'))
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
                        ->label(__('core::user.gender'))
                        ->rules(['in:unknown,male,female,other'])
                        ->required()
                        ->searchable()
                        ->options([
                            'unknown' => 'Unknown',
                            'female' => 'Female',
                            'male' => 'Male',
                            'other' => 'Other',
                        ])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('title')
                        ->label(__('core::user.title'))
                        ->rules(['max:255', 'string'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('first_name')
                        ->label(__('core::user.first_name'))
                        ->rules(['max:255', 'string'])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('last_name')
                        ->label(__('core::user.last_name'))
                        ->rules(['max:255', 'string'])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('email')
                        ->label(__('core::user.email'))
                        ->rules(['email'])
                        ->required()
                        ->unique(
                            'users',
                            'email',
                            fn(?Model $record): ?Model => $record
                        )
                        ->email()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('website')
                        ->label(__('core::user.website'))
                        ->rules(['max:255', 'string'])
                        ->nullable()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    RichEditor::make('description')
                        ->label(__('core::core.description'))
                        ->rules(['max:255', 'string'])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('password')
                        ->label(__('core::user.password'))
                        ->revealable()
                        ->required()
                        ->dehydrateStateUsing(fn($state) => Hash::make($state))
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
                        ->label(__('core::user.password_confirmation'))
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
                        ->label(__('core::user.current_password'))
                        ->revealable()
                        ->password()
                        ->rule('current_password')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),
                    TextInput::make('new_password')
                        ->label(__('core::user.new_password'))
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
                        ->label(__('core::user.new_password_confirmation'))
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

        ])->statePath('data');
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar_url'),
                // ImageColumn::make('avatar_url')
                //     ->defaultImageUrl(fn ($record): string => 'https://ui-avatars.com/api/?name='.$record->name)
                //     ->circular()
                //     ->label(__('core::user.avatar'))
                //     ->toggleable()
                //     ->size(50),
                TextColumn::make('name')
                    ->label(__('core::user.name'))
                    ->formatStateUsing(fn($state, User $user): string => $user->first_name . ' ' . $user->last_name)
                    ->toggleable()
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('email')
                    ->label(__('core::user.email'))
                    ->alignEnd()
                    ->sortable()
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                IconColumn::make('email_verified_at')
                    ->label(__('core::user.email_verified_at'))
                    ->sortable()
                    ->alignStart()
                    ->icon(
                        fn($record): string => is_null(
                            $record->email_verified_at
                        ) ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle'
                    )
                    ->colors([
                        'success' => fn($record): bool => $record->email_verified_at !== null,
                        'danger' => fn($record): bool => $record->email_verified_at === null,
                    ]),
                IconColumn::make('roles.name')
                    ->label(__('core::user.roles'))
                    ->sortable()
                    ->alignCenter()
                    ->icons([
                        'heroicon-o-shield-exclamation' => fn($record) => $record->roles->pluck('name')->contains('super_admin'),
                    ])
                    ->colors([
                        'warning' => fn($record) => $record->roles->pluck('name')->contains('super_admin'),
                    ]),
            ])
            ->filters([
                SelectFilter::make('language_id')
                    ->label(__('core::user.language_id'))
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

    #[Override]
    public static function getRelations(): array
    {
        return [
            // UserResource\RelationManagers\AuthorsRelationManager::class,
            // UserResource\RelationManagers\SessionsRelationManager::class,
            // UserResource\RelationManagers\SyncsRelationManager::class,
            // UserResource\RelationManagers\PlatformsRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('user.resources.user.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('user.resources.user.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('user.resources.user.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('user.resources.user.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('user.navigation_group');
    }

    #[Override]
    public static function getNavigationSort(): ?int
    {
        return config('user.navigation_sort') + 1;
    }
}
