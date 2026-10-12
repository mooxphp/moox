<?php

declare(strict_types=1);

namespace Moox\Zugferd;

use Moox\Core\MooxServiceProvider;
use Moox\Zugferd\Contracts\ZugferdNoteLabels;
use Moox\Zugferd\Support\EnglishNoteLabels;
use Spatie\LaravelPackageTools\Package;

class ZugferdServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('zugferd')
            ->hasConfigFile();

        $this->getMooxPackage()
            ->title('Moox ZUGFeRD')
            ->released(false)
            ->stability('dev')
            ->category('billing')
            ->usedFor([
                'generating valid XRechnung and ZUGFeRD e-invoices',
            ]);
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        $this->app->bindIf(ZugferdNoteLabels::class, EnglishNoteLabels::class);
        $this->app->singleton(ZugferdConverter::class);
    }
}
