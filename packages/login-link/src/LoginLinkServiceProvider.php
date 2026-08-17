<?php

declare(strict_types=1);

namespace Moox\LoginLink;

use Illuminate\Support\Facades\Route;
use Moox\Core\MooxServiceProvider;
use Moox\LoginLink\Commands\DemoIssueCommand;
use Moox\LoginLink\Commands\InstallCommand;
use Moox\LoginLink\Http\Controllers\DumpDemoController;
use Moox\LoginLink\Http\Controllers\PublicLoginLinkRedemptionController;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;
use Spatie\LaravelPackageTools\Package;

class LoginLinkServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('login-link')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                'create_login_links_table',
                'add_subject_and_process_to_login_links_table',
                'add_payload_and_nullable_panel_to_login_links_table',
                'create_login_link_processes_table',
                'add_soft_deletes_to_login_link_processes_table',
                'add_context_template_invalidate_to_login_link_processes_table',
            ])
            ->hasCommands([
                InstallCommand::class,
                DemoIssueCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RedemptionHandlerRegistry::class);
    }

    public function packageBooted(): void
    {
        $path = trim((string) config('login-link.public_consume_path', 'signed-link/{loginLink}'), '/');

        Route::middleware(['web'])
            ->get($path, PublicLoginLinkRedemptionController::class)
            ->middleware(['signed', 'throttle:10,1'])
            ->name('login-link.public.consume');

        Route::middleware(['web'])
            ->get('login-link/demo/dump', DumpDemoController::class)
            ->name('login-link.demo.dump');
    }
}
