<?php

declare(strict_types=1);

namespace Moox\Impersonate\Listeners;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use STS\FilamentImpersonate\Events\EnterImpersonation;
use STS\FilamentImpersonate\Events\LeaveImpersonation;

/**
 * Soft-depends on moox/audit: without it, enter/leave still works, nothing is logged.
 */
class LogImpersonation
{
    public function handleEnter(EnterImpersonation $event): void
    {
        $this->log('enter', $event->impersonator, $event->impersonated);
    }

    public function handleLeave(LeaveImpersonation $event): void
    {
        $this->log('leave', $event->impersonator, $event->impersonated);
    }

    private function log(string $action, mixed $impersonator, mixed $impersonated): void
    {
        $logger = 'Moox\\Audit\\Services\\MooxActivityLogger';

        if (! class_exists($logger)) {
            return;
        }

        if (! config('impersonate.audit.enabled', true)) {
            return;
        }

        $logName = (string) config('impersonate.audit.log_name', 'impersonate');
        $event = 'impersonation_'.$action;

        $options = [
            'event' => $event,
            'entry_type' => 'log',
            'properties' => [
                'impersonator_type' => is_object($impersonator) ? $impersonator::class : null,
                'impersonator_id' => $impersonator instanceof Authenticatable
                    ? $impersonator->getAuthIdentifier()
                    : null,
                'impersonated_type' => is_object($impersonated) ? $impersonated::class : null,
                'impersonated_id' => $impersonated instanceof Authenticatable
                    ? $impersonated->getAuthIdentifier()
                    : null,
            ],
        ];

        if ($impersonator instanceof Model) {
            $options['causer'] = $impersonator;
        }

        if ($impersonated instanceof Model) {
            $options['subject'] = $impersonated;
        }

        $logger::log($logName, $event, $options);
    }
}
