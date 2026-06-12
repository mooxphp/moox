<?php

declare(strict_types=1);

namespace Moox\EBilling\Moox\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
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
        ]);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        return app(self::class);
    }
}
