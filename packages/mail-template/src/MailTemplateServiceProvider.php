<?php

declare(strict_types=1);

namespace Moox\MailTemplate;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Core\MooxServiceProvider;
use Moox\Localization\Models\Localization;
use Moox\MailTemplate\Commands\InstallCommand;
use Moox\MailTemplate\Resources\MailLayoutResource\Pages\ListMailLayouts;
use Moox\MailTemplate\Resources\MailTemplateResource\Pages\ListMailTemplates;
use Spatie\LaravelPackageTools\Package;

class MailTemplateServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('mail-template')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_mail_layouts_table',
                'create_mail_layout_translations_table',
                'create_mail_templates_table',
                'create_mail_template_translations_table',
                'move_mail_logos_to_json',
            ])
            ->hasCommand(InstallCommand::class);
    }

    public function packageBooted(): void
    {
        if (! class_exists(Localization::class)) {
            return;
        }

        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListMailTemplates::class,
        );

        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListMailLayouts::class,
        );
    }

    public function mooxInfo(): array
    {
        $info = parent::mooxInfo();
        $info['migration_depends_on'] = ['moox/localization'];

        return $info;
    }
}
