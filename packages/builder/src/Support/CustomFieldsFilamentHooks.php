<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Builder\Registry\EntityRegistry;

final class CustomFieldsFilamentHooks
{
    private static bool $languageSelectorHooksRegistered = false;

    public static function register(): void
    {
        if (! class_exists(Filament::class)) {
            return;
        }

        if (! app()->bound('filament')) {
            return;
        }

        Filament::serving(function (): void {
            self::registerLanguageSelectorHooks();
        });
    }

    private static function registerLanguageSelectorHooks(): void
    {
        if (self::$languageSelectorHooksRegistered) {
            return;
        }

        $headerPageClasses = self::customFieldHeaderPageClasses();
        $listPageClasses = self::customFieldListPageClasses();

        if ($headerPageClasses === [] && $listPageClasses === []) {
            return;
        }

        self::$languageSelectorHooksRegistered = true;

        $headerRenderer = static function (): string {
            if (view()->exists('localization::lang-selector')) {
                return Blade::render('@include("localization::lang-selector")');
            }

            if (view()->exists('builder::lang-selector')) {
                return view('builder::lang-selector')->render();
            }

            return '';
        };

        foreach ($headerPageClasses as $pageClass) {
            FilamentView::registerRenderHook(
                PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE,
                $headerRenderer,
                scopes: $pageClass,
            );
        }

        foreach ($listPageClasses as $pageClass) {
            FilamentView::registerRenderHook(
                TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
                static function (): string {
                    if (view()->exists('builder::lang-selector')) {
                        return view('builder::lang-selector')->render();
                    }

                    if (view()->exists('localization::lang-selector')) {
                        return Blade::render('@include("localization::lang-selector")');
                    }

                    return '';
                },
                scopes: $pageClass,
            );
        }
    }

    /**
     * @return list<class-string>
     */
    private static function customFieldHeaderPageClasses(): array
    {
        return self::pageClassesForKeys(['edit', 'create', 'view']);
    }

    /**
     * @return list<class-string>
     */
    private static function customFieldListPageClasses(): array
    {
        return self::pageClassesForKeys(['index']);
    }

    /**
     * @param  list<string>  $pageKeys
     * @return list<class-string>
     */
    private static function pageClassesForKeys(array $pageKeys): array
    {
        $pageClasses = [];

        foreach (app(EntityRegistry::class)->all() as $definition) {
            $resourceClass = $definition['resource'] ?? null;

            if (! is_string($resourceClass)) {
                continue;
            }

            foreach ($pageKeys as $pageKey) {
                $registration = $resourceClass::getPages()[$pageKey] ?? null;

                if ($registration === null) {
                    continue;
                }

                $pageClasses[] = $registration->getPage();
            }
        }

        return array_values(array_unique($pageClasses));
    }
}
