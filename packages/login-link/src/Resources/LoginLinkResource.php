<?php

namespace Moox\LoginLink\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Config;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Resources\LoginLinkResource\Pages\ListPage;
use Override;

class LoginLinkResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = LoginLink::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-lock-clock-o';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label(__('core::user.email'))
                    ->maxLength(255),
                TextInput::make('ip_address')
                    ->label(__('core::core.ip_address'))
                    ->maxLength(255),
                TextInput::make('user_agent')
                    ->label(__('core::user.user_agent'))
                    ->maxLength(255)
                    ->columnSpan(2),
                DateTimePicker::make('expires_at')
                    ->label(__('core::core.expires_at')),
                DateTimePicker::make('used_at')
                    ->label(__('core::core.used_at')),
                Select::make('user_type')
                    ->label(__('core::user.user_type'))
                    ->options(function (): array {
                        $models = Config::get('login-link.user_models', []);

                        return array_flip($models);
                    })
                    ->reactive()
                    ->afterStateUpdated(function (Set $set, $state): void {
                        $set('user_id', null);
                    })
                    ->required(),

                Select::make('user_id')
                    ->label(__('core::user.user_id'))
                    ->options(function ($get) {
                        $userType = $get('user_type');
                        if (! $userType) {
                            return [];
                        }

                        return $userType::query()->pluck('name', 'id')->toArray();
                    })
                    ->required(),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('used')
                    ->label(__('login-link::translations.used'))
                    ->state(fn ($record): bool => ! empty($record->used_at))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->tooltip(fn ($record): string => empty($record->used_at)
                        ? __('login-link::translations.used_no')
                        : __('login-link::translations.used_yes'))
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('core::user.email'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('core::core.created_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label(__('core::core.expires_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('used_at')
                    ->label(__('core::core.used_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('user_type')
                    ->label(__('core::user.user_type'))
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label(__('core::user.user_id'))
                    ->getStateUsing(fn ($record) => optional($record->user)->name ?? 'unknown')
                    ->sortable(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
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
        return [];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('login-link.resources.login-link.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('login-link.resources.login-link.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('login-link.resources.login-link.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('login-link.resources.login-link.single');
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('login-link.navigation_group');
    }
}
