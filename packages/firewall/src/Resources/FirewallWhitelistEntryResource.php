<?php

namespace Moox\Firewall\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Moox\Firewall\Models\FirewallWhitelistEntry;
use Moox\Firewall\Resources\FirewallWhitelistEntryResource\Pages\ManageFirewallWhitelistEntries;
use Override;

class FirewallWhitelistEntryResource extends Resource
{
    protected static ?string $model = FirewallWhitelistEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('ip_address')
                ->label(__('firewall::translations.resource.ip_address'))
                ->placeholder('127.0.0.1')
                ->required()
                ->maxLength(45),
            TextInput::make('label')
                ->label(__('firewall::translations.resource.label'))
                ->maxLength(255),
            Toggle::make('is_active')
                ->label(__('firewall::translations.resource.active'))
                ->default(true),
            Toggle::make('allow_all_routes')
                ->label(__('firewall::translations.resource.allow_all_routes'))
                ->default(false)
                ->live()
                ->afterStateUpdated(function (bool $state, Set $set): void {
                    if ($state) {
                        $set('allowed_routes', null);
                    }
                }),
            Select::make('allowed_routes')
                ->label(__('firewall::translations.resource.allowed_routes'))
                ->hint(__('firewall::translations.resource.allowed_routes_hint'))
                ->helperText(fn (Get $get): ?string => $get('allow_all_routes') ? __('firewall::translations.resource.allowed_routes_ignored') : null)
                ->options(fn (): array => self::getCommonRoutePatternOptions())
                ->multiple()
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => self::searchRoutePatternOptions($search))
                ->getOptionLabelUsing(fn (string $value): string => $value)
                ->getOptionLabelsUsing(fn (array $values): array => array_combine($values, $values) ?: [])
                ->placeholder('admin/*')
                ->visible(fn (Get $get): bool => ! $get('allow_all_routes'))
                ->dehydrated(fn (Get $get): bool => ! (bool) $get('allow_all_routes')),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private static function getCommonRoutePatternOptions(): array
    {
        $prefixes = [];

        foreach (self::iterRouteUris() as $uri) {
            $first = Str::before($uri, '/');

            if ($first === '') {
                continue;
            }

            if (str_starts_with($first, '_')) {
                continue;
            }

            $prefixes[] = $first.'/*';
        }

        $prefixes = array_values(array_unique($prefixes));
        sort($prefixes);

        return [
            '/' => '/',
            'admin/*' => 'admin/*',
            'api/*' => 'api/*',
            'connect/*' => 'connect/*',
            ...array_combine($prefixes, $prefixes) ?: [],
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function searchRoutePatternOptions(string $search): array
    {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        $results = [];

        foreach (self::iterRouteUris() as $uri) {
            if (! str_contains($uri, $search)) {
                continue;
            }

            $results[$uri] = $uri;

            if (count($results) >= 50) {
                break;
            }
        }

        return $results;
    }

    /**
     * @return \Generator<int, string>
     */
    private static function iterRouteUris(): \Generator
    {
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $middleware = $route->gatherMiddleware();

            // Filter out API-only routes, keep everything else (Filament routes don't always
            // explicitly list the "web" group in `$route->middleware()`).
            if (in_array('api', $middleware, true)) {
                continue;
            }

            $uri = trim((string) $route->uri(), '/');

            // Represent root explicitly as "/".
            if ($uri === '') {
                yield '/';

                continue;
            }

            // Avoid clutter: hide internal routes from the selection UI.
            if (str_starts_with($uri, 'livewire/') || str_starts_with($uri, 'livewire-')) {
                continue;
            }

            if (str_starts_with($uri, '_')) {
                continue;
            }

            yield $uri;
        }
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ip_address')
                    ->label(__('firewall::translations.resource.ip_address'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('label')
                    ->label(__('firewall::translations.resource.label'))
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('firewall::translations.resource.active'))
                    ->boolean(),
                IconColumn::make('allow_all_routes')
                    ->label(__('firewall::translations.resource.all_routes'))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label(__('firewall::translations.resource.updated'))
                    ->since(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ManageFirewallWhitelistEntries::route('/'),
        ];
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return __('firewall::translations.resource.navigation_label');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return __('firewall::translations.resource.navigation_group');
    }
}
