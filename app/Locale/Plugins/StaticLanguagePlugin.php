<?php

declare(strict_types=1);

namespace App\Locale\Plugins;

use App\Locale\Resources\StaticLanguageResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;

class StaticLanguagePlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'staticlanguage';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            StaticLanguageResource::class,
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
