<?php

declare(strict_types=1);

namespace Moox\Customer\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Core\Support\Resources\ChildResourceRegistrar;
use Moox\Customer\Resources\CustomerResource;

class CustomerPlugin implements Plugin
{
    public function getId(): string
    {
        return 'customer';
    }

    public function register(Panel $panel): void
    {
        ChildResourceRegistrar::registerFromParentDefinition(
            $panel,
            CustomerResource::class,
            'customer',
            config('customer.resources.customer', []),
        );
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
