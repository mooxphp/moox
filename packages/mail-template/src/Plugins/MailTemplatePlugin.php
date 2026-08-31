<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\Route;
use Moox\MailTemplate\Http\Controllers\PreviewMailTemplateController;
use Moox\MailTemplate\Resources\MailTemplateResource;

class MailTemplatePlugin implements Plugin
{
    public function getId(): string
    {
        return 'mail-template';
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
