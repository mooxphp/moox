<?php

declare(strict_types=1);

namespace Moox\EBilling\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\EBilling\Resources\CreditNoteResource;
use Moox\EBilling\Resources\IgnoredDocumentResource;
use Moox\EBilling\Resources\InvoiceResource;

final class EBillingPlugin implements Plugin
{
    public function getId(): string
    {
        return 'e-billing';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            InvoiceResource::class,
            CreditNoteResource::class,
            IgnoredDocumentResource::class,
        ]);

        $panel->authenticatedRoutes(function (): void {
            require dirname(__DIR__, 2).'/routes/panel.php';
        });
    }

    public function boot(Panel $panel): void
    {
    }

    public static function make(): static
    {
        return app(self::class);
    }
}
