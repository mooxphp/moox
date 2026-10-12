<?php

declare(strict_types=1);

namespace Moox\Impersonate;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Moox\Core\MooxServiceProvider;
use Moox\Impersonate\Listeners\LogImpersonation;
use Spatie\LaravelPackageTools\Package;
use STS\FilamentImpersonate\Events\EnterImpersonation;
use STS\FilamentImpersonate\Events\LeaveImpersonation;

class ImpersonateServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('impersonate')
            ->hasConfigFile();
    }

    public function packageBooted(): void
    {
        Event::listen(EnterImpersonation::class, [LogImpersonation::class, 'handleEnter']);
        Event::listen(LeaveImpersonation::class, [LogImpersonation::class, 'handleLeave']);

        $this->registerSpatieCauserResolver();
    }

    /**
     * While impersonating, attribute Spatie/Moox activity causer to the real admin
     * (not the impersonated user). Soft-depends on moox/audit + spatie/laravel-activitylog.
     */
    private function registerSpatieCauserResolver(): void
    {
        $spatieResolver = 'Spatie\\Activitylog\\Support\\CauserResolver';
        $mooxResolver = 'Moox\\Audit\\Support\\CauserResolver';

        if (! class_exists($spatieResolver) || ! class_exists($mooxResolver)) {
            return;
        }

        $this->app->make($spatieResolver)->resolveUsing(function (mixed $subject = null) use ($mooxResolver): ?Model {
            if ($subject instanceof Model) {
                return $subject;
            }

            $causer = $mooxResolver::resolve();

            return $causer instanceof Model ? $causer : null;
        });
    }
}
