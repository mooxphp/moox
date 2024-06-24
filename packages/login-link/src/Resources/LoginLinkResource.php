<?php

namespace Moox\LoginLink\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Config;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Resources\LoginLinkResource\Pages\ListPage;
use Moox\LoginLink\Resources\LoginLinkResource\Widgets\LoginLinkWidgets;

class LoginLinkResource extends Resource
{
    protected static ?string $model = LoginLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label(__('login-link::translations.email'))
                    ->maxLength(255),
                TextInput::make('ip_address')
                    ->label(__('login-link::translations.ip_address'))
                    ->maxLength(255),
                TextInput::make('user_agent')
                    ->label(__('login-link::translations.user_agent'))
                    ->maxLength(255)
                    ->columnSpan(2),
                TextInput::make('token')
                    ->label(__('login-link::translations.token'))
                    ->maxLength(255)
                    ->columnSpan(2),
                DateTimePicker::make('expires_at'),
                DateTimePicker::make('used_at'),
                Select::make('user_type')
                    ->options(function () {
                        $models = Config::get('login-link.user_models', []);

                        return array_flip($models);
                    })
                    ->reactive()
                    ->afterStateUpdated(function (Set $set, $state) {
                        $set('user_id', null);
                    })
                    ->required(),

                Select::make('user_id')
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('used')
                    ->label('Valid')
                    ->icons([
                        'heroicon-o-x-circle' => fn ($record) => empty($record->used_at),
                        'heroicon-o-check-circle' => fn ($record) => ! empty($record->used_at),
                    ])
                    ->tooltip(fn ($record) => empty($record->used_at) ? 'Not Used' : 'Used')
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('login-link::translations.email'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('login-link::translations.expires_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label(__('login-link::translations.expires_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('used_at')
                    ->label(__('login-link::translations.used_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('user_type')
                    ->label(__('login-link::translations.user_type'))
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label(__('login-link::translations.username'))
                    ->getStateUsing(function ($record) {
                        return optional($record->user)->name ?? 'unknown';
                    })
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
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

    public static function getWidgets(): array
    {
        return [
            LoginLinkWidgets::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return __('login-link::translations.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('login-link::translations.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('login-link::translations.navigation_label');
    }

    public static function getBreadcrumb(): string
    {
        return __('login-link::translations.breadcrumb');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count());
    }

    public static function getNavigationGroup(): ?string
    {
        return __('login-link::translations.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('login-link.navigation_sort');
    }
}
