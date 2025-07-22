<?php

declare(strict_types=1);

namespace Moox\Draft;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Core\MooxServiceProvider;
use Moox\Draft\Moox\Entities\Drafts\Draft\Pages\ListDrafts;
use Spatie\LaravelPackageTools\Package;

class DraftServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('draft')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Draft')
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
                'Draft' => '%%PackageName%%',
                'draft' => '%%PackageSlug%%',
                'Draft is a simple Moox Entity, that can be used to create and manage simple entries, like logs.' => '%%Description%%',
                'building a simple Moox Entity, not used as installed package' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Draft' => '%%PackageName%%',
                'draft' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateEntityFiles([
                'config/draft.php',
                'database/factories/DraftFactory.php',
                'database/migrations/create_drafts_table.php.stub',
                'resources/lang/en/draft.php',
                'src/Models/Draft.php',
                'src/Moox/Entities/Drafts/Draft/Frontend/DraftFrontend.php',
                'src/Moox/Entities/Drafts/Draft/Pages/CreateDraft.php',
                'src/Moox/Entities/Drafts/Draft/Pages/EditDraft.php',
                'src/Moox/Entities/Drafts/Draft/Pages/ListDrafts.php',
                'src/Moox/Entities/Drafts/Draft/Pages/ViewDraft.php',
                'src/Moox/Entities/Drafts/Draft/DraftResource.php',
                'src/Moox/Plugins/DraftPlugin.php',
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
            scopes: ListDrafts::class
        );
    }
}
