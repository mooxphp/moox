<?php

namespace Moox\User\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\Media\Tables\Columns\CustomImageColumn;
use Moox\Security\FilamentActions\Passwords\SendPasswordResetLinksBulkAction;
use Moox\User\Models\User;
use Moox\User\Resources\UserResource\Pages\CreateUser;
use Moox\User\Resources\UserResource\Pages\EditUser;
use Moox\User\Resources\UserResource\Pages\ListUsers;
use Moox\User\Resources\UserResource\Pages\ViewUser;
use Override;

class UserResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-manage-accounts';

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                MediaPicker::make('avatar_url')
                    ->label('Avatar')
                    ->multiple()
                    ->maxFiles(4)
                    ->imageEditor()
                    ->panelLayout('grid'),

                TextInput::make('name')
                    ->label(__('core::core.name'))
                    ->rules(['max:255', 'string'])
                    ->required(),
                TextInput::make('slug')
                    ->label(__('core::core.slug'))
                    ->rules(['max:255', 'string']),

                Select::make('roles')
                    ->label(__('core::user.roles'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
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
                    ]),
                TextInput::make('title')
                    ->label(__('core::user.title'))
                    ->rules(['max:255', 'string'])
                    ->nullable(),
                TextInput::make('first_name')
                    ->label(__('core::user.first_name'))
                    ->rules(['max:255', 'string']),
                TextInput::make('last_name')
                    ->label(__('core::user.last_name'))
                    ->rules(['max:255', 'string']),
                TextInput::make('email')
                    ->label(__('core::user.email'))
                    ->rules(['email'])
                    ->required()
                    ->unique(
                        'users',
                        'email',
                        fn (?Model $record): ?Model => $record
                    )
                    ->email(),
                TextInput::make('website')
                    ->label(__('core::user.website'))
                    ->rules(['max:255', 'string'])
                    ->nullable(),

                RichEditor::make('description')
                    ->label(__('core::core.description'))
                    ->rules(['max:255']),

                TextInput::make('password')
                    ->label(__('core::user.password'))
                    ->revealable()
                    ->required()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->password()
                    ->visibleOn('create')
                    ->rule(Password::min(8)->mixedCase()->numbers()->symbols())
                    ->helperText('Your password must be at least 8 characters long and contain a mix of uppercase and lowercase letters, numbers, and symbols.'),

                TextInput::make('password_confirmation')
                    ->label(__('core::user.password_confirmation'))
                    ->requiredWith('password')
                    ->password()
                    ->same('password')
                    ->visibleOn('create'),
            ]),
            Section::make('Update Password')
                ->schema([
                    TextInput::make('current_password')
                        ->label(__('core::user.current_password'))
                        ->revealable()
                        ->password()
                        ->rule('current_password'),
                    TextInput::make('new_password')
                        ->label(__('core::user.new_password'))
                        ->revealable()
                        ->password()
                        ->rule(Password::min(8)->mixedCase()->numbers()->symbols())
                        ->helperText('Your password must be at least 8 characters long and contain a mix of uppercase and lowercase letters, numbers, and symbols.'),
                    TextInput::make('new_password_confirmation')
                        ->label(__('core::user.new_password_confirmation'))
                        ->password()
                        ->label('Confirm new password')
                        ->same('new_password')
                        ->requiredWith('new_password'),
                ])->visibleOn('edit'),
        ])->statePath('data')->columns(1);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                CustomImageColumn::make('avatar_url')
                    ->circular(),
                TextColumn::make('name')
                    ->label(__('core::user.name'))
                    ->formatStateUsing(fn ($state, User $user): string => $user->first_name.' '.$user->last_name)
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
                        fn ($record): string => is_null(
                            $record->email_verified_at
                        ) ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle'
                    )
                    ->colors([
                        'success' => fn ($record): bool => $record->email_verified_at !== null,
                        'danger' => fn ($record): bool => $record->email_verified_at === null,
                    ]),
                IconColumn::make('roles.name')
                    ->label(__('core::user.roles'))
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

            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions(array_filter([
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
}
