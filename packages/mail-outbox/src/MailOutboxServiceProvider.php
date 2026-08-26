<?php

declare(strict_types=1);

namespace Moox\MailOutbox;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class MailOutboxServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('mail-outbox')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_mail_templates_table',
                'add_subject_to_mail_templates_table',
            ])
            ->runsMigrations();

        $this->getMooxPackage()
            ->title('Moox Mail Outbox')
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
