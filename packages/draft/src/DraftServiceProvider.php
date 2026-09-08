<?php

declare(strict_types=1);

namespace Moox\Draft;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Audit\Support\AuditPackageRegistry;
use Moox\Core\MooxServiceProvider;
use Moox\Draft\Resources\DraftResource\Pages\ListDrafts;
use Spatie\LaravelPackageTools\Package;

class DraftServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('draft')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations('create_drafts_table', 'create_draft_translations_table')
            ->hasCommands();
    }

    public function packageBooted(): void
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListDrafts::class
        );

        if (
            class_exists(AuditPackageRegistry::class)
            && config('audit.enabled', true)
            && config('draft.audit.enabled', true)
        ) {
            AuditPackageRegistry::register('draft', config('draft.audit', []));
        }
    }
}
