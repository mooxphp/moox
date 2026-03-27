<?php

namespace Moox\Core\Support\Resources;

use Filament\Navigation\NavigationItem;
use Filament\Panel;

class ResourceNavigationRegistrar
{
    /**
     * @param  array<int, class-string>  $resources
     */
    public static function register(Panel $panel, array $resources): void
    {
        $panel->resources($resources);

        $navigationItems = [];

        foreach ($resources as $resource) {
            if (! is_string($resource) || ! class_exists($resource)) {
                continue;
            }

            $navigationItems[] = NavigationItem::make(fn (): string => $resource::getNavigationLabel())
                ->group(fn (): ?string => $resource::getNavigationGroup())
                ->icon(fn () => $resource::getNavigationIcon())
                ->activeIcon(fn () => $resource::getActiveNavigationIcon())
                ->badge(
                    fn (): ?string => $resource::getNavigationBadge(),
                    fn () => $resource::getNavigationBadgeColor(),
                )
                ->badgeTooltip(fn () => $resource::getNavigationBadgeTooltip())
                ->sort(fn (): ?int => $resource::getNavigationSort())
                ->isActiveWhen(fn (): bool => request()->routeIs($resource::getNavigationItemActiveRoutePattern()))
                ->url(fn (): string => $resource::getUrl());
        }

        if ($navigationItems !== []) {
            $panel->navigationItems($navigationItems);
        }
    }
}
