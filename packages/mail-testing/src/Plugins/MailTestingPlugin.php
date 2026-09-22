<?php

declare(strict_types=1);

namespace Moox\MailTesting\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\Route;
use Moox\MailTesting\Http\Controllers\PreviewMailTestingMessageController;
use Moox\MailTesting\Pages\MailTestingPage;
use Moox\MailTesting\Resources\MailTestingMessageResource;

class MailTestingPlugin implements Plugin
{
    public function getId(): string
    {
        return 'mail-testing';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                MailTestingPage::class,
            ])
            ->resources([
                MailTestingMessageResource::class,
            ]);

        $panel->authenticatedRoutes(function (): void {
            Route::get('mail-testing-messages/{mailTestingMessage}/preview', PreviewMailTestingMessageController::class)
                ->name('mail-testing-messages.preview');
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
