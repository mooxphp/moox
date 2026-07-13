<?php

declare(strict_types=1);

namespace Moox\Page;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Core\MooxServiceProvider;
use Moox\Page\Console\Commands\ExportPageSeedData;
use Moox\Page\Console\Commands\NormalizePagePermalinks;
use Moox\Page\Contracts\PageContentRenderer;
use Moox\Page\Resources\PageResource\Pages\ListPages;
use Moox\Page\Support\BlockContentRendererAdapter;
use Spatie\LaravelPackageTools\Package;

class PageServiceProvider extends MooxServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->bind(PageContentRenderer::class, function ($app): PageContentRenderer {
            $rendererClass = config('page.content_renderer', BlockContentRendererAdapter::class);

            return $app->make($rendererClass);
        });
    }

    public function configureMoox(Package $package): void
    {
        $package
            ->name('page')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations(
                'create_pages_table',
                'create_page_translations_table',
                'upgrade_pages_table',
            )
            ->hasCommands([
                ExportPageSeedData::class,
                NormalizePagePermalinks::class,
            ]);
    }

    public function packageBooted(): void
    {
        if (config('page.frontend.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListPages::class
        );
    }
}
