<?php

declare(strict_types=1);

namespace Moox\MailTemplate;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Core\MooxServiceProvider;
use Moox\MailTemplate\Commands\InstallCommand;
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
                'create_mail_templates_table',
                'create_mail_template_translations_table',
            ])
            ->hasCommand(InstallCommand::class);
    }

    public function packageBooted(): void
    {
        if (! view()->exists('localization::lang-selector')) {
            return;
        }

        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: ListMailTemplates::class,
        );
    }

    public function mooxInfo(): array
    {
        $info = parent::mooxInfo();
        $info['migration_depends_on'] = ['moox/localization'];

        return $info;
    }
}
