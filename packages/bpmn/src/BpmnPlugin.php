<?php

namespace Moox\Bpmn;

use Filament\Panel;
use Filament\Contracts\Plugin;
use Moox\Bpmn\Resources\Bpmns\BpmnResource;
use Filament\Support\Concerns\EvaluatesClosures;

class BpmnPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'bpmn';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            BpmnResource::class,
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
