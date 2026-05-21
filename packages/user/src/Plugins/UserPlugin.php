<?php

namespace Moox\User\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Core\Support\Resources\ChildResourceRegistrar;
use Moox\User\Resources\UserResource;

class UserPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'user';
    }

    public function register(Panel $panel): void
    {
        ChildResourceRegistrar::registerFromParentDefinition(
            $panel,
            UserResource::class,
            'user',
            config('user.resources.user', []),
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
