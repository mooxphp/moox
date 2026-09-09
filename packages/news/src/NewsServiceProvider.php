<?php

declare(strict_types=1);

namespace Moox\News;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Audit\Support\AuditPackageRegistry;
use Moox\Core\MooxServiceProvider;
use Moox\News\Resources\News\Pages\ListNews;
use Spatie\LaravelPackageTools\Package;

class NewsServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('news')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations(['create_news_table', 'create_news_translations_table']);
    }

    public function packageBooted(): void
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListNews::class
        );

        if (
            class_exists(AuditPackageRegistry::class)
            && config('audit.enabled', true)
            && config('news.audit.enabled', true)
        ) {
            AuditPackageRegistry::register('news', config('news.audit', []));
        }
    }
}
