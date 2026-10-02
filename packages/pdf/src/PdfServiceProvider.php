<?php

declare(strict_types=1);

namespace Moox\Pdf;

use Moox\Core\MooxServiceProvider;
use Moox\Pdf\Engines\TcpdfEngine;
use Spatie\LaravelPackageTools\Package;

class PdfServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('pdf')
            ->hasConfigFile();

        $this->getMooxPackage()
            ->title('Moox PDF')
            ->released(false)
            ->stability('dev')
            ->category('documents')
            ->usedFor([
                'rendering PDF documents through a stable Moox API',
            ]);
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        TcpdfEngine::registerFontPath();
    }
}
