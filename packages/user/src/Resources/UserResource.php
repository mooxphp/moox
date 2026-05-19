<?php

namespace Moox\User\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Moox\Core\Entities\BaseResource;
use Moox\User\Support\PasswordValidation;
use Moox\Core\Support\Resources\Concerns\HasScopedChildResource;
use Moox\Core\Support\Resources\ScopedResourceContext;
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

class UserResource extends BaseResource
{
    use HasResourceTabs;
    use HasScopedChildResource;

    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-manage-accounts';

    protected static ?string $recordTitleAttribute = 'name';

    public static function hasUserPolicy(): bool
    {
        return Gate::getPolicyFor(static::getModel()) !== null;
    }

    public static function canViewAllUsers(): bool
    {
        if (! static::hasUserPolicy()) {
            return true;
        }

        $policy = Gate::getPolicyFor(static::getModel());

        if (! $policy || ! method_exists($policy, 'viewAll')) {
            return true;
        }

        return Gate::allows('viewAll', static::getModel());
    }

    public static function canViewUserTabs(): bool
    {
        if (! static::hasUserPolicy()) {
            return true;
        }

        $policy = Gate::getPolicyFor(static::getModel());

        if (! $policy || ! method_exists($policy, 'viewTabs')) {
            return true;
        }

        return Gate::allows('viewTabs', static::getModel());
    }

    public static function securityPasswordResetActionsAvailable(): bool
    {
        return class_exists(\Moox\Security\FilamentActions\Passwords\SendPasswordResetLinksBulkAction::class)
            && (bool) config('security.actions.bulkactions.sendPasswordResetLinkBulkAction', false);
    }

    public static function shouldShowSendPasswordResetLinksBulkAction(): bool
    {
        return static::securityPasswordResetActionsAvailable()
            && static::canViewAllUsers();
    }

    public static function shouldShowSendPasswordResetLinkAction(): bool
    {
        return class_exists(\Moox\Security\FilamentActions\Passwords\SendPasswordResetLinkAction::class)
            && static::securityPasswordResetActionsAvailable()
            && static::canViewAllUsers();
    }

    public static function canManagePassword(?Model $record): bool
    {
        if ($record === null) {
            return true;
        }

        $authUser = auth()->user();

        return $authUser instanceof Model && $authUser->is($record);
    }

    public static function canSendPasswordResetTo(Model $record): bool
    {
        if (! static::hasUserPolicy()) {
            return true;
        }

        return Gate::allows('update', $record);
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        $supportsRoles = method_exists(static::getModel(), 'roles');

        return $schema->components([
            Section::make(__('core::core.general'))
                ->schema([
                    MediaPicker::make('avatar_url')
                        ->label('Avatar')
                        ->imageEditor()
                        ->panelLayout('grid'),

                    TextInput::make('name')
                        ->label(__('core::core.name'))
                        ->rules(['max:255', 'string'])
                        ->required(),

                    TextInput::make('slug')
                        ->label(__('core::core.slug'))
                        ->rules(['max:255', 'string']),

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
                ])
                ->columns(2),

            Section::make(__('core::core.contact'))
                ->schema([
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
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make(__('core::user.roles'))
                ->schema(array_filter([
                    $supportsRoles ? Select::make('roles')
                        ->label(__('core::user.roles'))
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable() : null,
                ]))
                ->columns(1)
                ->visible($supportsRoles),

            Section::make(__('core::user.password'))
                ->schema([
                    TextInput::make('password')
                        ->label(__('core::user.password'))
                        ->revealable()
                        ->required()
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->password()
                        ->rules(PasswordValidation::rules())
                        ->helperText(PasswordValidation::helperText())
                        ->visibleOn('create'),

                    TextInput::make('password_confirmation')
                        ->label(__('core::user.password_confirmation'))
                        ->requiredWith('password')
                        ->password()
                        ->same('password')
                        ->visibleOn('create'),

                    TextInput::make('current_password')
                        ->label(__('core::user.current_password'))
                        ->revealable()
                        ->password()
                        ->rule('current_password')
                        ->required(fn (Get $get): bool => filled($get('new_password')))
                        ->dehydrated(false)
                        ->hiddenOn('create'),

                    TextInput::make('new_password')
                        ->label(__('core::user.new_password'))
                        ->revealable()
                        ->password()
                        ->rules(PasswordValidation::rules())
                        ->helperText(PasswordValidation::helperText())
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->hiddenOn('create'),

                    TextInput::make('new_password_confirmation')
                        ->label(__('core::user.new_password_confirmation'))
                        ->password()
                        ->same('new_password')
                        ->requiredWith('new_password')
                        ->dehydrated(false)
                        ->hiddenOn('create'),
                ])
                ->columns(2)
                ->visible(fn (?Model $record, string $operation): bool => $operation === 'create' || static::canManagePassword($record)),
        ])->statePath('data')->columns(1);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        $supportsRoles = method_exists(static::getModel(), 'roles');

        return $table
            ->poll('60s')
            ->columns([
                CustomImageColumn::make('avatar_url')
                    ->circular()
                    ->defaultImageUrl(fn (User $record): string => 'https://ui-avatars.com/api/?name='.urlencode((string) ($record->name ?? 'User'))),
                TextColumn::make('name')
                    ->label(__('core::user.name'))
                    ->state(function (User $record): string {
                        $fullName = trim(($record->first_name ?? '').' '.($record->last_name ?? ''));

                        return filled($fullName) ? $fullName : (string) ($record->name ?? '');
                    })
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
                ...($supportsRoles ? [
                    TextColumn::make('roles')
                        ->label(__('core::user.roles'))
                        ->state(fn (User $record): array => $record->roles->pluck('name')->values()->all())
                        ->badge()
                        ->separator(', ')
                        ->limitList(3)
                        ->toggleable(),
                ] : []),
                TextColumn::make('deleted_at')
                    ->label(__('core::core.deleted'))
                    ->dateTime()
                    ->sortable()
                    ->visible(fn ($livewire): bool => ($livewire->activeTab ?? null) === 'deleted')
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn ($livewire): bool => ($livewire->activeTab ?? null) !== 'deleted'),
                ForceDeleteAction::make()
                    ->visible(fn ($livewire): bool => ($livewire->activeTab ?? null) === 'deleted'),
                RestoreAction::make()
                    ->visible(fn ($livewire): bool => ($livewire->activeTab ?? null) === 'deleted'),
            ])
            ->bulkActions(array_filter([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn ($livewire): bool => ($livewire->activeTab ?? null) !== 'deleted'),
                    ForceDeleteBulkAction::make()
                        ->visible(fn ($livewire): bool => ($livewire->activeTab ?? null) === 'deleted'),
                    RestoreBulkAction::make()
                        ->visible(fn ($livewire): bool => ($livewire->activeTab ?? null) === 'deleted'),
                ]),
                static::shouldShowSendPasswordResetLinksBulkAction() ?
                    SendPasswordResetLinksBulkAction::make() : null,
            ]));
    }

    public static function getTableQuery(?string $activeTab = null): Builder
    {
        $modelClass = static::getModel();
        $supportsRoles = method_exists($modelClass, 'roles');
        $authUser = auth()->user();

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true) && $activeTab === 'deleted') {
            $query = $modelClass::onlyTrashed();
            $query = ScopedResourceContext::applyScope($query, static::class);
        } else {
            $query = static::getEloquentQuery();
        }

        if ($supportsRoles) {
            $query->with(['roles']);
        }

        if ($authUser instanceof Model && ! static::canViewAllUsers()) {
            $query->whereKey($authUser->getKey());
        }

        return $query;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        $authUser = auth()->user();

        if ($authUser instanceof Model && ! static::canViewAllUsers()) {
            $query->whereKey($authUser->getKey());
        }

        return $query;
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
