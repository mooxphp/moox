<?php

declare(strict_types=1);

namespace Moox\Mjml;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class MjmlServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('mjml')
            ->hasConfigFile();

        $this->getMooxPackage()
            ->title('Moox MJML')
            ->released(false)
            ->stability('dev')
            ->category('mail')
            ->usedFor([
                'converting MJML markup to HTML through a stable Moox API',
            ]);
    }
}
