<?php

declare(strict_types=1);

namespace Moox\MailTemplate;

use Moox\Core\MooxServiceProvider;
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
                'add_subject_to_mail_templates_table',
            ])
            ->runsMigrations();

        $this->getMooxPackage()
            ->title('Moox Mail Template')
            ->released(false)
            ->stability('dev')
            ->category('mail')
            ->usedFor([
                'MJML mail templates with logo, footer, and Blade view links',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
