<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\Route;
use Moox\MailOutbox\Http\Controllers\PreviewMailTemplateController;
use Moox\MailOutbox\Resources\MailTemplateResource;

class MailOutboxPlugin implements Plugin
{
    public function getId(): string
    {
        return 'mail-outbox';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MailTemplateResource::class,
        ]);

        $panel->authenticatedRoutes(function (): void {
            Route::get('mail-templates/{mailTemplate}/preview', PreviewMailTemplateController::class)
                ->name('mail-templates.preview');
        });
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
