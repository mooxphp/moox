<?php

namespace Moox\Passkey\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Config;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Passkey\Models\Passkey;
use Moox\Passkey\Resources\PasskeyResource\Pages\ListPage;
use Moox\Passkey\Resources\PasskeyResource\Widgets\PasskeyWidgets;
use Override;

class PasskeyResource extends Resource
{
    use BaseInResource;
    use HasResourceTabs;

    protected static ?string $model = Passkey::class;

    protected static ?string $navigationIcon = 'gmdi-fingerprint-o';

    #[Override]
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label(__('core::passkey.title'))
                    ->maxLength(255)
                    ->required()
                    ->columnSpan(2),
                Textarea::make('credential_id')
                    ->label(__('core::passkey.credential_id'))
                    ->required(),
                Textarea::make('public_key')
                    ->label(__('core::passkey.public_key'))
                    ->required(),
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
                Select::make('device_id')
                    ->label(__('core::session.device_id'))
                    ->relationship('userDevice', 'title'),
                Select::make('session_id')
                    ->label(__('core::session.session_id'))
                    ->string()
                    ->relationship('userSession', 'id'),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('core::passkey.title'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('core::core.created_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('core::core.updated_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('user_type')
                    ->label(__('core::user.user_type'))
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label(__('core::user.user_id'))
                    ->getStateUsing(fn ($record) => optional($record->user)->name ?? 'unknown')
                    ->sortable(),
                TextColumn::make('device_id')
                    ->label(__('core::session.device_id'))
                    ->sortable(),
                TextColumn::make('session_id')
                    ->label(__('core::session.session_id'))
                    ->sortable(),
            ])
            ->defaultSort('title', 'desc')
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
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
        return [
            // PasskeyWidgets::class,
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('passkey.resources.passkey.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('passkey.resources.passkey.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('passkey.resources.passkey.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('passkey.resources.passkey.single');
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('passkey.navigation_group');
    }
}
