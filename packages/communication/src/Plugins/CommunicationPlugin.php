<?php

declare(strict_types=1);

namespace Moox\Communication\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Communication\Resources\CommunicationResource;
use Moox\Communication\Resources\CommunicationRuleResource;
use Moox\Communication\Resources\CommunicationTypeResource;

class CommunicationPlugin implements Plugin
{
    public function getId(): string
    {
        return 'communication';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            CommunicationResource::class,
            CommunicationTypeResource::class,
            CommunicationRuleResource::class,
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
