<?php

declare(strict_types=1);

namespace Moox\PdfParser;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class PdfParserServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('pdf-parser')
            ->hasConfigFile();

        $this->getMooxPackage()
            ->title('Moox PDF Parser')
            ->released(false)
            ->stability('dev')
            ->category('billing')
            ->usedFor([
                'extracting text from PDF invoices and documents',
            ]);
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        $this->app->singleton(PdfParser::class, function ($app) {
            return new PdfParser(
                config('pdf-parser.pdftotext_path')
            );
        });
    }
}
