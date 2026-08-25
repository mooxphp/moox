<?php

declare(strict_types=1);

namespace Moox\Mjml;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class MjmlServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package->name('mjml');

        $this->getMooxPackage()
            ->title('Moox MJML')
            ->released(false)
            ->stability('dev')
            ->category('mail')
            ->usedFor([
                'requiring spatie/mjml-php so Blade MJML can be converted to HTML',
            ]);
    }
}
