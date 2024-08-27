<?php

namespace Moox\UserSession\Resources;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Moox\UserDevice\Resources\UserDeviceResource\Pages\ViewPage;
use Moox\UserSession\Models\UserSession;
use Moox\UserSession\Resources\UserSessionResource\Pages\ListPage;
use Moox\UserSession\Resources\UserSessionResource\Widgets\UserSessionWidgets;

class UserSessionResource extends Resource
{
    protected static ?string $model = UserSession::class;

    protected static ?string $navigationIcon = 'gmdi-safety-check';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //TextInput::make('id')
                //    ->maxLength(255),
                TextInput::make('user_id')
                    ->label(__('core::user.user_id'))
                    ->maxLength(255),
                TextInput::make('ip_address')
                    ->label(__('core::core.ip_address'))
                    ->maxLength(255),
                TextInput::make('user_agent')
                    ->label(__('core::user.user_agent'))
                    ->maxLength(255),
                //Textarea::make('payload'),
                //TextInput::make('last_activity')
                //    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_id')
                    ->label(__('core::user.user_id'))
                    ->getStateUsing(function ($record) {
                        try {
                            return $record->user ? $record->user->name : 'unknown';
                        } catch (\Exception $e) {
                            Log::error('Failed to retrieve user name: '.$e->getMessage());

                            return 'unknown';
                        }
                    })
                    ->sortable(),

                TextColumn::make('id')
                    ->label(__('core::core.id'))
                    ->sortable(),
                TextColumn::make('user_type')
                    ->label(__('core::user.user_type'))
                    ->sortable(),
                TextColumn::make('device_id')
                    ->label(__('core::session.device_id'))
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label(__('core::core.ip_address'))
                    ->sortable(),
                TextColumn::make('whitlisted')
                    ->label(__('core::session.whitelisted'))
                    ->sortable(),
                TextColumn::make('last_activity')
                    ->label(__('core::session.last_activity'))
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('user_id', 'desc')
            ->actions([
                ViewAction::make(),
                DeleteAction::make()
                    ->label(__('core::core.drop'))
                    ->action(function ($record) {
                        try {
                            $record->delete();
                            Notification::make()
                                ->title('Deleted successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Log::error('Failed to delete record: '.$e->getMessage());
                            Notification::make()
                                ->title('Error on deleting')
                                ->success()
                                ->send();
                        }
                    }),
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
            //'view' => ViewPage::route('/{record}'),

        ];
    }

    public static function getWidgets(): array
    {
        return [
            UserSessionWidgets::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return config('user-session.resources.session.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('user-session.resources.session.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('user-session.resources.session.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('user-session.resources.session.single');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return config('user-session.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('user-session.navigation_sort') + 3;
    }
}
