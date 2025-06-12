<?php

declare(strict_types=1);

namespace Moox\News;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Core\MooxServiceProvider;
use Moox\News\Moox\Entities\News\News\Pages\ListNews;
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
            ->hasMigrations()
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox News')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                '%%UsedFor%%',
            ])
            ->alternatePackages([
                '', // optional alternative package (e.g. moox/post)
            ])
            ->templateFor([
                'creating simple Laravel packages',
            ])
            ->templateReplace([
                'News' => '%%PackageName%%',
                'news' => '%%PackageSlug%%',
                'News is a simple Moox Entity, that can be used to create and manage simple entries, like logs.' => '%%Description%%',
                'building a simple Moox Entity, not used as installed package' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'News' => '%%PackageName%%',
                'news' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateEntityFiles([
                'config/news.php',
                'database/factories/NewsFactory.php',
                'database/migrations/create_news_table.php.stub',
                'resources/lang/en/news.php',
                'src/Models/News.php',
                'src/Moox/Entities/News/News/Frontend/NewsFrontend.php',
                'src/Moox/Entities/News/News/Pages/CreateNews.php',
                'src/Moox/Entities/News/News/Pages/EditNews.php',
                'src/Moox/Entities/News/News/Pages/ListNews.php',
                'src/Moox/Entities/News/News/Pages/ViewNews.php',
                'src/Moox/Entities/News/News/NewsResource.php',
                'src/Moox/Plugins/NewsPlugin.php',
            ])
            ->templateRemove([
                '',
            ]);
    }

    public function packageBooted(): void
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_TOGGLE_COLUMN_TRIGGER_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListNews::class
        );
    }
}
