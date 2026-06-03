<?php

declare(strict_types=1);

namespace Moox\Company\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Moox\Company\Resources\CompanyResource;
use Moox\Core\Support\Resources\ChildResourceRegistrar;

class CompanyPlugin implements Plugin
{
    public function getId(): string
    {
        return 'company';
    }

    public function register(Panel $panel): void
    {
        ChildResourceRegistrar::registerFromParentDefinition(
            $panel,
            CompanyResource::class,
            'company',
            config('company.resources.company', []),
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
