<?php

namespace Moox\UserDevice\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as DbSchema;
use Moox\Core\Entities\BaseResource;
use Moox\Core\Support\Resources\Concerns\HasScopedChildResource;
use Moox\Core\Support\Resources\ScopedResourceContext;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Resources\UserDeviceResource\Pages\ListPage;
use Override;
use Spatie\Permission\PermissionRegistrar;

class UserDeviceResource extends BaseResource
{
    use HasResourceTabs;
    use HasScopedChildResource;

    protected static ?string $model = UserDevice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-devices-o';

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $query->with('user');

        if (! static::shouldScopeToAuthenticatedUser()) {
            return $query;
        }

        $authUser = filament()->auth()->user();

        if (! $authUser) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('user_id', $authUser->getAuthIdentifier())
            ->where('user_type', $authUser::class);
    }

    /**
     * Moox Core's tabs trait calls `getTableQuery($activeTab)`. We accept the
     * optional parameter and delegate to `getEloquentQuery()` so scoping is
     * consistently applied.
     */
    public static function getTableQuery(?string $activeTab = null): Builder
    {
        if (filled($activeTab)) {
            static::setCurrentTab($activeTab);
        }

        return static::getEloquentQuery();
    }

    protected static function shouldScopeToAuthenticatedUser(): bool
    {
        if (config('user-device.scope_to_authenticated_user', false) === true) {
            return true;
        }

        $authUser = filament()->auth()->user();

        if (! $authUser) {
            return false;
        }

        if (! static::permissionSystemAvailable()) {
            return ! config('user-device.allow_all_devices_without_shield', false);
        }

        return ! static::isShieldAdmin($authUser);
    }

    protected static function permissionSystemAvailable(): bool
    {
        if (! class_exists(PermissionRegistrar::class)) {
            return false;
        }

        return DbSchema::hasTable('permissions') && DbSchema::hasTable('roles');
    }

    protected static function isShieldAdmin(object $user): bool
    {
        if (! method_exists($user, 'hasRole')) {
            return false;
        }

        $roleName = (string) config('filament-shield.super_admin.name', 'super_admin');

        return (bool) $user->hasRole($roleName);
    }

    public static function shouldShowTabsForUser(?object $user): bool
    {
        if (! $user) {
            return false;
        }

        if (! static::permissionSystemAvailable()) {
            return false;
        }

        return static::isShieldAdmin($user);
    }

    #[Override]
    protected static function resolveDefaultNavigationGroup(): ?string
    {
        return config('user-device.navigation_group');
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('core::core.title'))
                    ->maxLength(255)
                    ->disabled(),
                TextInput::make('slug')
                    ->label(__('core::core.slug'))
                    ->maxLength(255)
                    ->disabled(),
                DateTimePicker::make('updated_at')
                    ->label(__('core::core.updated_at'))
                    ->disabled(),
                DateTimePicker::make('created_at')
                    ->label(__('core::core.created_at'))
                    ->disabled(),
                TextInput::make('user_type')
                    ->label(__('core::user.user_type'))
                    ->required()
                    ->disabled(),
                TextInput::make('user_id')
                    ->label(__('core::user.user_id'))
                    ->required()
                    ->disabled(),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('platform')
                    ->label(__('core::sync.platform'))
                    ->icon(fn ($record): string => match ($record->platform) {
                        'Mobile' => 'heroicon-o-device-phone-mobile',
                        'Desktop' => 'heroicon-o-computer-desktop',
                        default => 'heroicon-o-computer-desktop',
                    }),
                IconColumn::make('whitelisted')
                    ->label(__('user-device::translations.device_trusted'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('title')
                    ->label(__('core::core.title'))
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label(__('core::user.user_id'))
                    ->getStateUsing(fn ($record) => optional($record->user)->name ?? 'unknown')
                    ->sortable(),
                static::getScopeTableColumn(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('core::core.updated_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('core::core.created_at'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('user_search')
                    ->label('User')
                    ->visible(fn (): bool => static::permissionSystemAvailable() && static::isShieldAdmin(filament()->auth()->user()))
                    ->form([
                        TextInput::make('q')
                            ->label('Name / E-Mail / ID')
                            ->placeholder('z. B. aziz, admin@example.com, 8'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $q = trim((string) ($data['q'] ?? ''));

                        if ($q === '') {
                            return $query;
                        }

                        return $query->whereHasMorph('user', '*', function (Builder $userQuery) use ($q): void {
                            $userQuery->where(function (Builder $sub) use ($q): void {
                                if (is_numeric($q)) {
                                    $sub->orWhere($sub->getModel()->getQualifiedKeyName(), (int) $q);
                                }

                                $sub
                                    ->orWhere('name', 'like', "%{$q}%")
                                    ->orWhere('email', 'like', "%{$q}%")
                                    ->orWhere('first_name', 'like', "%{$q}%")
                                    ->orWhere('last_name', 'like', "%{$q}%");
                            });
                        });
                    }),
            ])
            ->defaultSort('title', 'desc')
            ->recordActions([
                DeleteAction::make()
                    ->label(__('user-device::translations.device_delete'))
                    ->requiresConfirmation()
                    ->modalHeading(__('user-device::translations.device_delete_modal_heading'))
                    ->modalDescription(__('user-device::translations.device_delete_modal_description'))
                    ->visible(fn (UserDevice $record): bool => static::permissionSystemAvailable() && static::isShieldAdmin(filament()->auth()->user()))
                    ->successNotificationTitle(__('user-device::translations.device_delete_success_title')),
                Action::make('trust')
                    ->label(__('user-device::translations.device_trust'))
                    ->requiresConfirmation()
                    ->modalHeading(__('user-device::translations.device_trust_modal_heading'))
                    ->modalDescription(__('user-device::translations.device_trust_modal_description'))
                    ->visible(fn (UserDevice $record): bool => static::permissionSystemAvailable() && static::isShieldAdmin(filament()->auth()->user()) && ! $record->whitelisted)
                    ->action(function (UserDevice $record): void {
                        $record->update(['whitelisted' => true]);

                        Notification::make()
                            ->title(__('user-device::translations.device_trust_success_title'))
                            ->success()
                            ->send();
                    }),
                Action::make('untrust')
                    ->label(__('user-device::translations.device_untrust'))
                    ->requiresConfirmation()
                    ->modalHeading(__('user-device::translations.device_untrust_modal_heading'))
                    ->modalDescription(__('user-device::translations.device_untrust_modal_description'))
                    ->visible(fn (UserDevice $record): bool => static::permissionSystemAvailable() && static::isShieldAdmin(filament()->auth()->user()) && $record->whitelisted)
                    ->action(function (UserDevice $record): void {
                        $record->update(['whitelisted' => false]);

                        Notification::make()
                            ->title(__('user-device::translations.device_untrust_success_title'))
                            ->warning()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                static::getAssignScopeBulkAction(),
                DeleteBulkAction::make()
                    ->label(__('user-device::translations.device_delete'))
                    ->requiresConfirmation()
                    ->modalHeading(__('user-device::translations.device_delete_modal_heading'))
                    ->modalDescription(__('user-device::translations.device_delete_modal_description'))
                    ->successNotificationTitle(__('user-device::translations.device_delete_success_title'))
                    ->visible(fn (): bool => static::permissionSystemAvailable() && static::isShieldAdmin(filament()->auth()->user())),
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListPage::route('/'),
        ];
    }

    #[Override]
    public static function getWidgets(): array
    {
        return [
            //
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('user-device.resources.devices.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('user-device.resources.devices.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        $scoped = ScopedResourceContext::getDefinitionValue(static::class, 'navigation_label');
        if ($scoped !== null) {
            return (string) $scoped;
        }

        return config('user-device.resources.devices.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('user-device.resources.devices.single');
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        $scoped = ScopedResourceContext::getDefinitionValue(static::class, 'should_register_navigation');

        return $scoped !== null ? (bool) $scoped : true;
    }
}
