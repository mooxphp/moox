<?php

namespace Moox\UserSession\Resources;

use Exception;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\UserSession\Models\UserSession;
use Moox\UserSession\Resources\UserSessionResource\Pages\ListPage;
use Override;

class UserSessionResource extends BaseItemResource
{
    use HasResourceTabs;

    protected static ?string $model = UserSession::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-safety-check';

    protected static ?string $recordTitleAttribute = 'id';

    public static function enableCreate(): bool
    {
        return false;
    }

    public static function enableEdit(): bool
    {
        return false;
    }

    public static function enableView(): bool
    {
        return false;
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_id')
                    ->label(__('core::user.user'))
                    ->getStateUsing(function (UserSession $record): string {
                        $user = $record->resolveUser();

                        if ($user && filled($user->name ?? null)) {
                            return (string) $user->name;
                        }

                        if (filled($record->user_id)) {
                            return '#'.$record->user_id;
                        }

                        return '—';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $inner) use ($search): void {
                            $inner
                                ->where('user_id', 'like', "%{$search}%")
                                ->orWhere('ip_address', 'like', "%{$search}%")
                                ->orWhere('user_agent', 'like', "%{$search}%")
                                ->orWhere('user_type', 'like', "%{$search}%");

                            $modelClass = config('auth.providers.users.model');

                            if (is_string($modelClass) && class_exists($modelClass)) {
                                $userIds = $modelClass::query()
                                    ->where(function (Builder $userQuery) use ($search): void {
                                        $userQuery
                                            ->where('name', 'like', "%{$search}%")
                                            ->orWhere('email', 'like', "%{$search}%");
                                    })
                                    ->limit(100)
                                    ->pluck((new $modelClass)->getKeyName());

                                if ($userIds->isNotEmpty()) {
                                    $inner->orWhereIn('user_id', $userIds);
                                }
                            }
                        });
                    })
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label(__('core::core.ip_address'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user_agent')
                    ->label(__('core::user.user_agent'))
                    ->limit(40)
                    ->tooltip(fn (UserSession $record): ?string => $record->user_agent)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('device_id')
                    ->label(__('core::session.device_id'))
                    ->getStateUsing(function (UserSession $record): string {
                        $device = $record->resolveDevice();

                        if ($device && filled($device->title ?? null)) {
                            return (string) $device->title;
                        }

                        if (filled($record->device_id)) {
                            return '#'.$record->device_id;
                        }

                        return '—';
                    })
                    ->tooltip(fn (UserSession $record): ?string => filled($record->device_id) ? (string) $record->device_id : null)
                    ->toggleable(),
                IconColumn::make('whitelisted')
                    ->label(__('core::session.whitelisted'))
                    ->boolean()
                    ->getStateUsing(fn (UserSession $record): bool => (bool) $record->whitelisted)
                    ->sortable(),
                TextColumn::make('last_activity')
                    ->label(__('core::session.last_activity'))
                    ->sortable()
                    ->since(),
                TextColumn::make('id')
                    ->label(__('core::core.id'))
                    ->limit(12)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user_type')
                    ->label(__('core::user.user_type'))
                    ->formatStateUsing(function (?string $state): string {
                        if (blank($state)) {
                            return '—';
                        }

                        $parts = explode('\\', $state);

                        return count($parts) > 3
                            ? implode('\\', array_slice($parts, -3))
                            : $state;
                    })
                    ->tooltip(fn (UserSession $record): ?string => $record->user_type)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('ip_address')
                    ->label(__('core::core.ip_address'))
                    ->form([
                        TextInput::make('ip')
                            ->label(__('core::core.ip_address')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $ip = trim((string) ($data['ip'] ?? ''));

                        if ($ip === '') {
                            return $query;
                        }

                        return $query->where('ip_address', 'like', "%{$ip}%");
                    }),
                Filter::make('user')
                    ->label(__('core::user.user'))
                    ->form([
                        TextInput::make('q')
                            ->label(__('core::user.user'))
                            ->placeholder('Name / E-Mail / ID'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $q = trim((string) ($data['q'] ?? ''));

                        if ($q === '') {
                            return $query;
                        }

                        return $query->where(function (Builder $inner) use ($q): void {
                            $inner->where('user_id', 'like', "%{$q}%");

                            $modelClass = config('auth.providers.users.model');

                            if (is_string($modelClass) && class_exists($modelClass)) {
                                $userIds = $modelClass::query()
                                    ->where(function (Builder $userQuery) use ($q): void {
                                        $userQuery
                                            ->where('name', 'like', "%{$q}%")
                                            ->orWhere('email', 'like', "%{$q}%");

                                        if (is_numeric($q)) {
                                            $userQuery->orWhere((new $modelClass)->getKeyName(), (int) $q);
                                        }
                                    })
                                    ->limit(100)
                                    ->pluck((new $modelClass)->getKeyName());

                                if ($userIds->isNotEmpty()) {
                                    $inner->orWhereIn('user_id', $userIds);
                                }
                            }
                        });
                    }),
                TernaryFilter::make('whitelisted')
                    ->label(__('core::session.whitelisted'))
                    ->nullable(),
            ])
            ->defaultSort('last_activity', 'desc')
            ->recordActions([
                DeleteAction::make()
                    ->label(__('core::core.drop'))
                    ->action(function ($record): void {
                        try {
                            $record->delete();
                            Notification::make()
                                ->title(__('core::core.deleted'))
                                ->success()
                                ->send();
                        } catch (Exception $exception) {
                            Log::error('Failed to delete record: '.$exception->getMessage());
                            Notification::make()
                                ->title(__('core::core.failed'))
                                ->danger()
                                ->send();
                        }
                    }),
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
        return config('user-session.resources.session.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('user-session.resources.session.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('user-session.resources.session.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('user-session.resources.session.single');
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('user-session.navigation_group');
    }
}
