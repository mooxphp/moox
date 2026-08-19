<?php

declare(strict_types=1);

namespace Moox\LoginLink\Handlers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Moox\LoginLink\Contracts\RedemptionHandler;
use Moox\LoginLink\Events\ProcessLinkAcknowledged;
use Moox\LoginLink\Models\LoginLink;

/**
 * Packaged public-context examples: no Auth::login, fire the ack event, land on an English result page.
 */
abstract class PublicExampleRedemptionHandler implements RedemptionHandler
{
    abstract protected function sessionKey(): string;

    abstract protected function redirectRoute(): string;

    public function handle(LoginLink $loginLink, ?string $panelId): ?RedirectResponse
    {
        $subject = $loginLink->subject()->first();

        if (! $subject instanceof Model) {
            return null;
        }

        ProcessLinkAcknowledged::dispatch($loginLink, $subject, $panelId);

        $process = $loginLink->processDefinition();

        session()->put($this->sessionKey(), [
            'email' => $loginLink->email,
            'process_title' => $process?->title,
            'process_slug' => $process?->slug,
            'payload' => $loginLink->payload ?? [],
        ]);

        return redirect()->route($this->redirectRoute());
    }
}
