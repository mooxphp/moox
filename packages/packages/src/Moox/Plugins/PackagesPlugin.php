<?php

namespace Moox\Packages\Moox\Plugins;

use Filament\Panel;
use Filament\Contracts\Plugin;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Packages\Moox\Entities\Packages\Package\PackagesResource;

class PackagesPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'packages';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                PackagesResource::class,
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
