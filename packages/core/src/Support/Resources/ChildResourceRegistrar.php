<?php

namespace Moox\Core\Support\Resources;

use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Support\Scopes\ScopeValue;

class ChildResourceRegistrar
{
    protected static ?bool $hasScopesTable = null;

    /**
     * @param  class-string  $parentResource
     * @param  array<string, mixed>  $parentDefinition
     */
    public static function registerFromParentDefinition(Panel $panel, string $parentResource, string $parentKey, array $parentDefinition = []): void
    {
        $parentScope = value($parentDefinition['scope'] ?? null)
            ?? ScopeValue::forKeyString(
                $parentKey,
                boundary: value($parentDefinition['boundary'] ?? $parentDefinition['mode'] ?? null),
                source: value($parentDefinition['source'] ?? $parentDefinition['target'] ?? null),
                context: value($parentDefinition['context'] ?? null),
            );

        $scopes = $parentDefinition['scopes'] ?? $parentDefinition['children'] ?? [];

        $panel->resources([
            $parentResource,
        ]);

        static::register(
            $panel,
            $parentResource,
            is_array($scopes) ? $scopes : [],
            (string) ($parentDefinition['slug'] ?? $parentKey),
            $parentScope,
        );
    }

    /**
     * @param  class-string  $parentResource
     * @param  array<string, array<string, mixed>>  $children
     */
    public static function register(Panel $panel, string $parentResource, array $children, string $parentSlug, ?string $parentScope = null): void
    {
        $resourceConfigurations = [];
        $navigationItems = [];
        $items = [];

        foreach ($children as $childKey => $child) {
            $resource = $child['resource'] ?? null;

            if (! is_string($resource) || ! class_exists($resource)) {
                continue;
            }

            $configurationKey = static::resolveConfigurationKey($parentSlug, $childKey, $child);
            $definition = static::resolveDefinition($parentResource, $childKey, $child, $parentSlug, $parentScope);

            $items[] = [
                'child' => $child,
                'resource' => $resource,
                'configurationKey' => $configurationKey,
                'definition' => $definition,
            ];
        }

        foreach ($items as $item) {
            /** @var array<string, mixed> $child */
            $child = $item['child'];
            /** @var class-string $resource */
            $resource = $item['resource'];
            /** @var string $configurationKey */
            $configurationKey = $item['configurationKey'];
            /** @var array<string, mixed> $definition */
            $definition = $item['definition'];

            if (blank($definition['scope_match'] ?? null)) {
                $definition['scope_match'] = static::resolveDefaultScopeMatchFromDatabase($definition['scope'] ?? null);
            }

            ScopedResourceRegistry::register($resource, $configurationKey, $definition);

            $resourceConfigurations[] = $resource::make($configurationKey);

            if (static::shouldShowNavigationForScope($definition['scope'] ?? null)) {
                $navigationItems[] = NavigationItem::make(
                    fn (): string => value($child['label'] ?? null)
                        ?? value($child['navigation_label'] ?? null)
                        ?? $resource::withConfiguration($configurationKey, fn (): string => $resource::getNavigationLabel())
                )
                    ->group(
                        fn () => value($child['navigation_group'] ?? null) ?? $parentResource::getNavigationGroup()
                    )
                    ->parentItem(
                        fn () => value($child['navigation_parent_item'] ?? null) ?? $parentResource::getNavigationLabel()
                    )
                    ->icon(
                        fn () => value($child['icon'] ?? null) ?? $resource::getNavigationIcon()
                    )
                    ->activeIcon(
                        fn () => value($child['active_icon'] ?? null) ?? $resource::getActiveNavigationIcon()
                    )
                    ->badge(
                        fn (): ?string => value($child['badge'] ?? null)
                            ?? $resource::withConfiguration($configurationKey, fn (): ?string => $resource::getNavigationBadge()),
                        fn () => value($child['badge_color'] ?? null)
                            ?? $resource::withConfiguration($configurationKey, fn () => $resource::getNavigationBadgeColor()),
                    )
                    ->badgeTooltip(
                        fn () => value($child['badge_tooltip'] ?? null)
                            ?? $resource::withConfiguration($configurationKey, fn () => $resource::getNavigationBadgeTooltip())
                    )
                    ->sort(
                        fn () => value($child['sort'] ?? null)
                            ?? $parentResource::getNavigationSort()
                            ?? $resource::getNavigationSort()
                            ?? PHP_INT_MAX
                    )
                    ->isActiveWhen(
                        fn (): bool => request()->routeIs(
                            $resource::withConfiguration($configurationKey, fn (): string|array => $resource::getNavigationItemActiveRoutePattern())
                        )
                    )
                    ->url(
                        fn (): string => $resource::getUrl(configuration: $configurationKey)
                    );
            }
        }

        if ($resourceConfigurations !== []) {
            $panel->resources($resourceConfigurations);
        }

        if ($navigationItems !== []) {
            $panel->navigationItems($navigationItems);
        }
    }

    /**
     * @param  array<string, mixed>  $child
     */
    protected static function isEnabled(array $child): bool
    {
        // Config should define scopes/resources, but runtime visibility/activation is driven by DB (scopes.is_active).
        // Keeping this method for backward compatibility, but it no longer blocks registration.
        return true;
    }

    /**
     * @param  array<string, mixed>  $child
     */
    protected static function resolveConfigurationKey(string $parentSlug, string $childKey, array $child): string
    {
        return (string) ($child['key'] ?? "{$parentSlug}-{$childKey}");
    }

    /**
     * @param  array<string, mixed>  $child
     * @return array<string, mixed>
     */
    protected static function resolveDefinition(string $parentResource, string $childKey, array $child, string $parentSlug, ?string $parentScope = null): array
    {
        $childSlug = trim((string) ($child['slug'] ?? $childKey), '/');
        $childOrigin = value($child['origin'] ?? null) ?: $childKey;
        $childScope = value($child['scope'] ?? null);
        $childContext = value($child['context'] ?? null);
        $childBoundary = value($child['boundary'] ?? $child['mode'] ?? null);
        $childSource = value($child['source'] ?? $child['target'] ?? null);

        if (blank($childScope) && filled($parentScope) && filled($childOrigin)) {
            $childScope = ScopeValue::deriveChildString(
                $parentScope,
                (string) $childOrigin,
                context: is_string($childContext) ? $childContext : null,
                boundary: is_string($childBoundary) ? $childBoundary : null,
                source: is_string($childSource) ? $childSource : null,
            );
        }

        return array_merge($child, [
            'origin' => $childOrigin,
            'slug' => trim($parentSlug, '/').'/'.$childSlug,
            'scope' => $childScope,
            'navigation_label' => value($child['label'] ?? null) ?? value($child['navigation_label'] ?? null),
            'navigation_group' => value($child['navigation_group'] ?? null) ?? $parentResource::getNavigationGroup(),
            'navigation_parent_item' => value($child['navigation_parent_item'] ?? null) ?? $parentResource::getNavigationLabel(),
            'navigation_icon' => value($child['icon'] ?? null),
            'active_navigation_icon' => value($child['active_icon'] ?? null),
            'navigation_badge' => value($child['badge'] ?? null),
            'navigation_badge_color' => value($child['badge_color'] ?? null),
            'navigation_badge_tooltip' => value($child['badge_tooltip'] ?? null),
            'navigation_sort' => value($child['sort'] ?? null),
            'scope_match' => value($child['scope_match'] ?? null),
            'should_register_navigation' => false,
        ]);
    }

    protected static function shouldShowNavigationForScope(string|ScopeValue|null $scope): bool
    {
        $scopeString = ScopeValue::toStringOrNull($scope);

        if ($scopeString === null || ! static::hasScopesTable()) {
            return true;
        }

        $active = DB::table('scopes')
            ->where('scope', $scopeString)
            ->value('is_active');

        return (bool) $active;
    }

    protected static function hasScopesTable(): bool
    {
        return static::$hasScopesTable ??= Schema::hasTable('scopes');
    }

    protected static function resolveDefaultScopeMatchFromDatabase(string|ScopeValue|null $scope): string
    {
        $parsed = ScopeValue::parse($scope);

        if ($parsed === null || ! static::hasScopesTable()) {
            return ScopedResourceContext::MATCH_CONTEXT;
        }

        $activeBoundaryCount = (int) DB::table('scopes')
            ->where('origin', $parsed->origin())
            ->where('source', $parsed->source())
            ->where('context', $parsed->context())
            ->where('is_active', true)
            ->distinct()
            ->count('boundary');

        return $activeBoundaryCount > 1
            ? ScopedResourceContext::MATCH_EXACT
            : ScopedResourceContext::MATCH_CONTEXT;
    }
}
