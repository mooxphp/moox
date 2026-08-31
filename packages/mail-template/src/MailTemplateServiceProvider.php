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
            ->hasMigrations('create_mail_templates_table');
    }
}
