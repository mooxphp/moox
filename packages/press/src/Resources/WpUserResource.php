<?php

namespace Moox\Press\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Press\Models\WpUser;
use Moox\Press\Resources\WpUserResource\Pages\CreateWpUser;
use Moox\Press\Resources\WpUserResource\Pages\EditWpUser;
use Moox\Press\Resources\WpUserResource\Pages\ListWpUsers;
use Moox\Press\Resources\WpUserResource\Pages\ViewWpUser;
use Moox\Press\Resources\WpUserResource\RelationManagers\WpUserMetaRelationManager;
use Moox\Security\FilamentActions\Passwords\SendPasswordResetLinksBulkAction;
use Moox\Security\Helper\PasswordHash;
use Override;

class WpUserResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = WpUser::class;

    protected static ?string $navigationIcon = 'gmdi-manage-accounts';

    protected static ?string $recordTitleAttribute = 'display_name';

    protected static function getCapabilitiesOptions(): array
    {
        $userCapabilities = config('press.wp_roles');

        return collect($userCapabilities)->mapWithKeys(fn ($key, $value) => [$key => $value])->toArray();
    }

    protected static function getUserLevel($serializedRole)
    {
        $roleArray = unserialize($serializedRole);

        if (is_array($roleArray)) {
            $role = array_key_first($roleArray);

            if ($role) {
                $role = strtolower($role);

                $userLevels = config('press.wp_user_levels');

                return $userLevels[$role] ?? 0;
            }
        }

        return 0;
    }

    #[Override]
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Userdata')->schema([
                Grid::make(['default' => 0])->schema([
                    FileUpload::make('image_url')
                        ->label('Avatar')
                        ->avatar()
                        ->disk('press')
                        ->directory(now()->year.'/'.sprintf('%02d', now()->month))
                        ->preserveFilenames()
                        ->afterStateUpdated(function ($state, $set): void {
                            if ($state) {
                                $tempPath = $state->store('livewire-tmp');
                                $originalName = $state->getClientOriginalName();
                                $set('original_name', $originalName);
                                $set('temporary_file_path', $tempPath);
                            }
                        }),

                    TextInput::make('user_login')
                        ->label(__('core::user.user_login'))
                        ->rules(['max:60', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Select::make(config('press.wordpress_prefix').'capabilities')
                        ->label('Role')
                        ->options(self::getCapabilitiesOptions())
                        ->required()
                        ->columnSpan(12)
                        ->dehydrateStateUsing(fn ($state) => $state) // Speichert den Rollenwert direkt
                        ->live()
                        ->afterStateUpdated(function ($state, $set): void {
                            $roleLevel = self::getUserLevel($state);
                            $set(config('press.wordpress_prefix').'user_level', $roleLevel);
                        }),

                    TextInput::make('first_name')
                        ->label(__('core::user.first_name'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->live(debounce: 1000)
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->afterStateUpdated(function ($state, $set, $get): void {
                            $set('user_nicename', strtolower($get('first_name').'-'.$get('last_name')));
                            $set('display_name', ucwords($get('first_name').' '.$get('last_name')));
                        }),

                    TextInput::make('last_name')
                        ->label(__('core::user.last_name'))
                        ->rules(['max:255', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ])
                        ->live(debounce: 1000)
                        ->afterStateUpdated(function ($state, $set, $get): void {
                            $set('user_nicename', strtolower($get('first_name').'-'.$get('last_name')));
                            $set('display_name', ucwords($get('first_name').' '.$get('last_name')));
                        }),

                    TextInput::make('display_name')
                        ->label(__('core::user.display_name'))
                        ->rules(['max:50', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('user_nicename')
                        ->label(__('core::user.user_nicename'))
                        ->rules(['max:50', 'string'])
                        ->required()
                        ->readonly()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('user_email')
                        ->label(__('core::user.user_email'))
                        ->rules(['max:100', 'string'])
                        ->required()

                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('user_url')
                        ->label(__('core::user.user_url'))
                        ->rules(['max:100', 'string'])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    DateTimePicker::make('user_registered')
                        ->label(__('core::user.user_registered'))
                        ->rules(['date'])
                        ->required()
                        ->readonly()
                        ->default(now())
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    /* Must be provided by Sync, use a static maybe?
                    Select::make('platforms')
                        ->label('Platforms')
                        ->multiple()
                        ->options(function () {
                            return \Moox\Sync\Models\Platform::pluck('name', 'id')->toArray();
                        })
                        ->afterStateHydrated(function ($component, $state, $record) {
                            if ($record && class_exists('\Moox\Sync\Services\PlatformRelationService')) {
                                $platformService = app(\Moox\Sync\Services\PlatformRelationService::class);
                                $platforms = $platformService->getPlatformsForModel($record);
                                $component->state($platforms->pluck('id')->toArray());
                            }
                        })
                        ->dehydrated(false)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, $record) {
                            if ($record && class_exists('\Moox\Sync\Services\PlatformRelationService')) {
                                $platformService = app(\Moox\Sync\Services\PlatformRelationService::class);
                                $platformService->syncPlatformsForModel($record, $state ?? []);
                            }
                        })
                        ->preload()
                        ->searchable()
                        ->visible(fn () => class_exists('\Moox\Sync\Models\Platform'))
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),
                        */
                ]),
            ]),

            Section::make('Metadata')->schema([
                Grid::make(['default' => 2])->schema([
                    TextInput::make('nickname')
                        ->label(__('core::user.nickname'))
                        ->rules(['max:100', 'string']),

                    Select::make('rich_editing')
                        ->label(__('core::user.rich_editing'))
                        ->options([
                            'true' => 'true',
                            'false' => 'false',
                        ])
                        ->default('true')
                        ->selectablePlaceholder(false),

                    TextInput::make('description')
                        ->label(__('core::user.description'))
                        ->rules(['max:100', 'string']),

                    Select::make('comment_shortcuts')
                        ->label(__('core::user.comment_shortcuts'))
                        ->options([
                            'true' => 'true',
                            'false' => 'false',
                        ])
                        ->default('false')
                        ->selectablePlaceholder(false),

                    Select::make('admin_color')
                        ->label(__('core::user.admin_color'))
                        ->options([
                            'default' => 'Default',
                            'fresh' => 'Fresh',
                            'light' => 'Light',
                            'modern' => 'Modern',
                            'blue' => 'Blue',
                            'coffee' => 'Coffee',
                            'ectoplasm' => 'Ectoplasm',
                            'midnight' => 'Midnight',
                            'ocean' => 'Ocean',
                            'sunrise' => 'Sunrise',
                        ]),
                    Select::make('show_admin_bar_front')
                        ->label(__('core::user.show_admin_bar_front'))
                        ->options([
                            'true' => 'true',
                            'false' => 'false',
                        ])
                        ->default('true')
                        ->selectablePlaceholder(false),

                    TextInput::make(config('press.wordpress_prefix').'user_level')
                        ->label(__('core::user.user_level'))
                        ->readonly()
                        ->default(fn ($state) => self::getUserLevel($state)),

                    TextInput::make('dismissed_wp_pointers')
                        ->label(__('core::user.dismissed_wp_pointers')),

                    TextInput::make(config('press.wordpress_prefix').'dashboard_quick_press_last_post_id')
                        ->label(__('core::user.dashboard_quick_press_last_post_id')),
                ]),
            ]),

            Section::make('Password')->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('user_pass')
                        ->revealable()
                        ->label(__('core::user.user_pass'))
                        ->password()
                        ->visibleOn('create')
                        ->dehydrateStateUsing(function ($state): string {
                            $passwordHash = new PasswordHash(8, true);

                            return $passwordHash->HashPassword($state);
                        })
                        ->rule(Password::min(8))
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('password_confirmation')
                        ->label(__('core::user.password_confirmation'))
                        ->requiredWith('user_pass')
                        ->password()
                        ->same('user_pass')
                        ->visibleOn('create')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                ]),
            ])->visibleOn('create'),

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
                        ->rule(Password::min(8))
                        // ->helperText('Your password must be at least 8 characters long and contain a mix of uppercase and lowercase letters, numbers, and symbols.')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),
                    TextInput::make('new_password_confirmation')
                        ->password()
                        ->label(__('core::user.new_password_confirmation'))
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

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                ImageColumn::make('attachment.image_url')
                    ->label(__('core::user.avatar'))
                    ->circular()
                    ->disk('press')
                    ->size(50)
                    ->toggleable()
                    ->limit(50),

                TextColumn::make('display_name')
                    ->label(__('core::user.name'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),

                TextColumn::make('user_email')
                    ->label(__('core::user.user_email'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),

                TextColumn::make('user_login')
                    ->label(__('core::user.user_login'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),

                TextColumn::make(config('press.wordpress_prefix').'capabilities')
                    ->label(__('core::user.role'))
                    ->toggleable()
                    ->limit(50)
                    ->formatStateUsing(function ($state) {
                        $capabilitiesOptions = self::getCapabilitiesOptions();

                        return $capabilitiesOptions[$state] ?? __('No Role');
                    }),

            ])
            ->filters([])
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
            WpUserMetaRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListWpUsers::route('/'),
            'create' => CreateWpUser::route('/create'),
            'view' => ViewWpUser::route('/{record}'),
            'edit' => EditWpUser::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('press.resources.user.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('press.resources.user.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('press.resources.user.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('press.resources.user.single');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('press.user_navigation_group');
    }

    #[Override]
    public static function getNavigationSort(): ?int
    {
        return config('press.user_navigation_sort') + 1;
    }
}
