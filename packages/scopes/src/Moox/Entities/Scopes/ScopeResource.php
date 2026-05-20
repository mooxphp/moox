<?php

declare(strict_types=1);

namespace Moox\Scopes\Moox\Entities\Scopes;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Moox\Core\Models\Concerns\HasScopedModel;
use Moox\Core\Models\Scope;
use Moox\Core\Services\ScopeRegistry;
use Moox\Core\Support\Scopes\ScopeValue;

class ScopeResource extends Resource
{
    protected static ?string $model = Scope::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Scopes';

    protected static string|\UnitEnum|null $navigationGroup = 'DEV';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Select::make('origin')
                        ->label('Origin')
                        ->helperText('What kind of record this scope applies to (e.g. media, category).')
                        ->options(function (): array {
                            $registry = app(ScopeRegistry::class);
                            $origins = array_keys($registry->getOrigins());

                            $origins = array_values(array_filter($origins, function (string $origin) use ($registry): bool {
                                if (static::allowedSourcesForOrigin($origin) === []) {
                                    return false;
                                }

                                return static::isOriginResourceRegistered($origin, $registry);
                            }));

                            return array_combine($origins, $origins);
                        })
                        ->required()
                        ->disabled(fn (?Scope $record): bool => $record !== null)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get, ?Scope $record): void {
                            if ($record !== null) {
                                return;
                            }

                            $origin = is_string($state) ? $state : '';
                            $allowedSources = static::allowedSourcesForOrigin($origin);
                            $currentSource = (string) ($get('source') ?? '');
                            if ($currentSource !== '' && $allowedSources !== [] && ! in_array($currentSource, $allowedSources, true)) {
                                $set('source', null);
                                $set('context', null);
                                $set('boundary', null);
                            }

                            $set('scope', static::buildScopeKey($get));
                        }),
                    Select::make('source')
                        ->label('Source')
                        ->helperText('Usually your parent/bundle key (e.g. draft, career).')
                        ->options(function (callable $get): array {
                            $all = array_keys(app(ScopeRegistry::class)->getSources());
                            $origin = (string) ($get('origin') ?? '');

                            if (blank($origin)) {
                                return array_combine($all, $all);
                            }

                            $allowed = static::allowedSourcesForOrigin($origin);

                            // If we can't infer allowed sources from config, fall back to source=origin (if registered).
                            if (empty($allowed)) {
                                if (in_array($origin, $all, true)) {
                                    return [$origin => $origin];
                                }

                                return [];
                            }

                            $filtered = array_values(array_intersect($all, $allowed));

                            return array_combine($filtered, $filtered);
                        })
                        ->required()
                        ->disabled(fn (?Scope $record, callable $get): bool => $record !== null || blank($get('origin')))
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get, ?Scope $record): void {
                            if ($record !== null) {
                                return;
                            }

                            $set('scope', static::buildScopeKey($get));
                        }),
                    Select::make('context')
                        ->label('Context')
                        ->helperText('Concrete thing inside the selected source (bundle).')
                        ->options(fn (callable $get): array => static::contextOptionsForSource((string) ($get('source') ?? '')))
                        ->searchable()
                        ->required()
                        ->disabled(fn (?Scope $record, callable $get): bool => $record !== null || blank($get('source')))
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get, ?Scope $record): void {
                            if ($record !== null) {
                                return;
                            }

                            $set('scope', static::buildScopeKey($get));
                        }),
                    Select::make('boundary')
                        ->label('Boundary')
                        ->helperText('Visibility bucket inside the same context.')
                        ->options(fn (): array => array_combine(ScopeValue::allowedBoundaries(), ScopeValue::allowedBoundaries()))
                        ->required()
                        ->disabled(fn (?Scope $record, callable $get): bool => $record !== null || blank($get('context')))
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get, ?Scope $record): void {
                            if ($record !== null) {
                                return;
                            }

                            $set('scope', static::buildScopeKey($get));
                        }),

                    TextInput::make('scope')
                        ->label('Scope key')
                        ->helperText('Generated from origin/source/context/boundary.')
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->formatStateUsing(function ($state, $record, $get): string {
                            if ($record instanceof Scope && filled($record->scope)) {
                                return (string) $record->scope;
                            }

                            return static::buildScopeKey($get);
                        })
                        ->unique(ignoreRecord: true),

                    TextInput::make('label')
                        ->label('Label')
                        ->placeholder('e.g. Media Private')
                        ->nullable(),

                    Toggle::make('is_active')
                        ->label('Active'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    /**
     * Infer which sources are meaningful for a given origin by scanning
     * parent resource definitions in package configs.
     *
     * We treat each parent resource key as the "source" and look at
     * `config('<package>.resources.<parent>.scopes.allowed')` to see which child origins
     * are offered under that parent.
     *
     * @return array<int, string>
     */
    protected static function allowedSourcesForOrigin(string $origin): array
    {
        $origin = trim($origin);
        if ($origin === '') {
            return [];
        }

        $allowed = [];

        $packages = (array) config('core.packages', []);
        foreach (array_keys($packages) as $packageKey) {
            $resources = (array) config($packageKey.'.resources', []);
            foreach ($resources as $parentKey => $definition) {
                if (! is_array($definition)) {
                    continue;
                }

                $scopes = $definition['scopes'] ?? null;
                if (! is_array($scopes)) {
                    continue;
                }

                // Support structured config: ['allowed' => [...], 'registry' => [...]].
                $scopes = is_array($scopes['allowed'] ?? null) ? $scopes['allowed'] : $scopes;

                // Legacy structure: scopes may contain registry metadata alongside definitions.
                if (array_key_exists('registry', $scopes) && is_array($scopes['registry'] ?? null)) {
                    $scopes = array_diff_key($scopes, ['registry' => true]);
                }

                foreach ($scopes as $scopeKey => $scopeDefinition) {
                    if (! is_array($scopeDefinition)) {
                        continue;
                    }

                    $childOrigin = $scopeDefinition['origin'] ?? null;
                    if (! is_string($childOrigin) || $childOrigin === '') {
                        // Infer origin from key (e.g. media_public -> media).
                        $childOrigin = is_string($scopeKey) ? explode('_', $scopeKey, 2)[0] : '';
                    }

                    if ($childOrigin === $origin) {
                        $allowed[] = (string) $parentKey;
                        break;
                    }
                }
            }
        }

        $allowed = array_values(array_unique(array_filter($allowed, static fn ($v): bool => $v !== '')));
        sort($allowed);

        return $allowed;
    }

    /**
     * Check if the origin is ready: its resource is registered in the panel
     * and its model uses the HasScopedModel trait.
     */
    protected static function isOriginResourceRegistered(string $origin, ScopeRegistry $registry): bool
    {
        $modelClass = $registry->resolveOriginModel($origin);

        if (! $modelClass || ! class_exists($modelClass)) {
            return false;
        }

        if (! in_array(HasScopedModel::class, class_uses_recursive($modelClass), true)) {
            return false;
        }

        $panelResources = filament()->getCurrentPanel()?->getResources() ?? [];
        $packages = (array) config('core.packages', []);

        foreach (array_keys($packages) as $packageKey) {
            $resources = (array) config($packageKey.'.resources', []);
            foreach ($resources as $definition) {
                if (! is_array($definition)) {
                    continue;
                }

                $scopes = $definition['scopes'] ?? null;
                if (! is_array($scopes)) {
                    continue;
                }

                $allowed = is_array($scopes['allowed'] ?? null) ? $scopes['allowed'] : [];

                foreach ($allowed as $key => $childDef) {
                    if (! is_array($childDef)) {
                        continue;
                    }

                    $childOrigin = $childDef['origin'] ?? (is_string($key) ? explode('_', $key, 2)[0] : '');

                    if ($childOrigin === $origin && isset($childDef['resource'])) {
                        return in_array($childDef['resource'], $panelResources, true);
                    }
                }
            }
        }

        return false;
    }

    protected static function buildScopeKey(callable $get): string
    {
        $origin = (string) ($get('origin') ?? '');
        $source = (string) ($get('source') ?? '');
        $context = (string) ($get('context') ?? '');
        $boundary = (string) ($get('boundary') ?? '');

        if (blank($origin) || blank($source) || blank($context) || blank($boundary)) {
            return '';
        }

        return "{$origin}:{$source}:{$context}:{$boundary}";
    }

    /**
     * Context buckets for a source — same derivation as `moox:scope` / ScopesSyncCommand.
     *
     * @return array<string, string>
     */
    protected static function contextOptionsForSource(string $source): array
    {
        if (blank($source)) {
            return [];
        }

        $contexts = [];

        foreach (array_keys((array) config('core.packages', [])) as $packageKey) {
            $definition = config("{$packageKey}.resources.{$source}");

            if (! is_array($definition)) {
                continue;
            }

            if (filled($definition['context'] ?? null)) {
                $contexts[] = (string) $definition['context'];
            }

            $scopes = $definition['scopes'] ?? null;

            if (! is_array($scopes)) {
                continue;
            }

            $allowed = is_array($scopes['allowed'] ?? null) ? $scopes['allowed'] : $scopes;

            if (array_key_exists('registry', $allowed)) {
                $allowed = array_diff_key($allowed, ['registry' => true]);
            }

            $baseScope = value($definition['scope'] ?? null)
                ?? ScopeValue::forKeyString(
                    $source,
                    boundary: value($definition['boundary'] ?? $definition['mode'] ?? null),
                    source: value($definition['source'] ?? $definition['target'] ?? null),
                    context: value($definition['context'] ?? null),
                );

            foreach ($allowed as $originKey => $scopeDefinition) {
                if (! is_array($scopeDefinition)) {
                    continue;
                }

                if (filled($scopeDefinition['context'] ?? null)) {
                    $contexts[] = (string) $scopeDefinition['context'];

                    continue;
                }

                $origin = value($scopeDefinition['origin'] ?? null) ?: (is_string($originKey) ? $originKey : null);

                if (! is_string($origin) || $origin === '') {
                    continue;
                }

                $derived = ScopeValue::deriveChildString(
                    $baseScope,
                    $origin,
                    context: is_string(value($scopeDefinition['context'] ?? null)) ? value($scopeDefinition['context']) : null,
                    boundary: is_string(value($scopeDefinition['boundary'] ?? $scopeDefinition['mode'] ?? null)) ? value($scopeDefinition['boundary'] ?? $scopeDefinition['mode']) : null,
                    source: is_string(value($scopeDefinition['source'] ?? $scopeDefinition['target'] ?? null)) ? value($scopeDefinition['source'] ?? $scopeDefinition['target']) : null,
                );

                if (! is_string($derived) || $derived === '') {
                    continue;
                }

                try {
                    $parsed = ScopeValue::parse($derived);

                    if ($parsed) {
                        $contexts[] = $parsed->context();
                    }
                } catch (\Throwable) {
                    // ignore invalid derived scope
                }
            }
        }

        $dbContexts = Scope::query()
            ->where('source', $source)
            ->whereNotNull('context')
            ->where('context', '!=', '')
            ->orderBy('context')
            ->pluck('context')
            ->all();

        $contexts = array_values(array_unique(array_filter(
            [...$contexts, ...$dbContexts],
            static fn ($value): bool => filled($value),
        )));
        sort($contexts);

        if ($contexts === []) {
            return [];
        }

        return array_combine($contexts, $contexts);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display')
                    ->label('Scope')
                    ->getStateUsing(function (Scope $record): string {
                        $base = filled($record->label) ? (string) $record->label : (string) $record->scope;

                        return "{$base} — {$record->source}/{$record->context} ({$record->boundary})";
                    })
                    ->wrap()
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search): void {
                            $q->where('scope', 'like', "%{$search}%")
                                ->orWhere('label', 'like', "%{$search}%")
                                ->orWhere('origin', 'like', "%{$search}%")
                                ->orWhere('source', 'like', "%{$search}%")
                                ->orWhere('context', 'like', "%{$search}%")
                                ->orWhere('boundary', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('scope')
                    ->label('Raw')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('label')
                    ->label('Label')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('origin')->toggleable(),
                TextColumn::make('source')->toggleable(),
                TextColumn::make('context')->toggleable(),
                TextColumn::make('boundary')->toggleable(),
                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->toggleable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScopes::route('/'),
            'create' => Pages\CreateScope::route('/create'),
            'edit' => Pages\EditScope::route('/{record}/edit'),
        ];
    }
}
