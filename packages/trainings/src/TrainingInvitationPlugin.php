<?php

namespace Moox\Training;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Training\Resources\TrainingInvitationResource;

class TrainingInvitationPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'training-invitations';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            TrainingInvitationResource::class,
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
