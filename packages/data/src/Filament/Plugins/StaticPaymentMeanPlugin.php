<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Data\Filament\Resources\StaticPaymentMeanResource;

class StaticPaymentMeanPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'staticpaymentmean';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            StaticPaymentMeanResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
