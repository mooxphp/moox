<?php

declare(strict_types=1);

namespace Moox\LoginLink\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Moox\LoginLink\Models\LoginLink;

class LoginLinkRedemptionService
{
    public function __construct(
        protected RedemptionHandlerRegistry $handlers,
    ) {
    }

    public function redeem(int|string $loginLinkId, string $panelId): ?RedirectResponse
    {
        return DB::transaction(function () use ($loginLinkId, $panelId) {
            $loginLink = LoginLink::query()
                ->whereKey($loginLinkId)
                ->lockForUpdate()
                ->first();

            if (! $loginLink || $loginLink->used_at !== null || $loginLink->expires_at->isPast()) {
                return null;
            }

            if ((string) $loginLink->panel_id !== (string) $panelId) {
                return null;
            }

            $process = $this->resolveProcess($loginLink);
            $handler = $this->handlers->get($process);

            if ($handler === null) {
                return null;
            }

            $result = $handler->handle($loginLink, $panelId);

            if ($result === null) {
                return null;
            }

            $loginLink->update(['used_at' => now()]);

            return $result;
        });
    }

    /**
     * Until the process discriminator column lands (#3), missing process defaults to login (BC).
     */
    protected function resolveProcess(LoginLink $loginLink): string
    {
        $process = $loginLink->getAttribute('process')
            ?? $loginLink->getAttribute('expiry_job');

        if (is_string($process) && $process !== '') {
            return $process;
        }

        return RedemptionHandlerRegistry::DEFAULT_PROCESS;
    }
}
