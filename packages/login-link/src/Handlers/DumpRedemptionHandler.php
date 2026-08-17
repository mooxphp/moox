<?php

declare(strict_types=1);

namespace Moox\LoginLink\Handlers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Moox\LoginLink\Contracts\RedemptionHandler;
use Moox\LoginLink\Models\LoginLink;

/**
 * Demo handler: no auth. Puts redeem context into the session and redirects
 * to the dump view so you can see process / subject / payload.
 */
class DumpRedemptionHandler implements RedemptionHandler
{
    public const SESSION_KEY = 'login_link.dump';

    public function handle(LoginLink $loginLink, ?string $panelId): ?RedirectResponse
    {
        $subject = $loginLink->subject()->first();

        if (! $subject instanceof Model) {
            return null;
        }

        $process = $loginLink->processDefinition();

        session()->put(self::SESSION_KEY, [
            'redeemed_at' => now()->toIso8601String(),
            'panel_id_arg' => $panelId,
            'auth_check' => Auth::guard()->check(),
            'process' => [
                'slug' => $process?->slug,
                'title' => $process?->title,
                'context' => $process?->context,
                'handler_key' => $process?->handler_key,
                'template_key' => $process?->template_key,
                'invalidate_prior' => $process?->invalidate_prior,
            ],
            'login_link' => [
                'id' => $loginLink->getKey(),
                'email' => $loginLink->email,
                'panel_id' => $loginLink->panel_id,
                'process' => $loginLink->process,
                'payload' => $loginLink->payload ?? [],
                'used_at' => now()->toIso8601String(),
            ],
            'subject' => [
                'type' => $subject::class,
                'id' => $subject->getKey(),
                'attributes' => $subject->toArray(),
            ],
        ]);

        return redirect()->route('login-link.demo.dump');
    }
}
