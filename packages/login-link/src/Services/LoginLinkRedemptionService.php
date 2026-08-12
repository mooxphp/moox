<?php

declare(strict_types=1);

namespace Moox\LoginLink\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Models\LoginLinkProcess;

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

            $handlerKey = $this->resolveHandlerKey($loginLink);
            $handler = $this->handlers->get($handlerKey);

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
     * Prefer the process definition's handler_key; fall back to the link's process
     * slug (BC when slug and handler key match, or no definition exists yet).
     */
    protected function resolveHandlerKey(LoginLink $loginLink): string
    {
        $processSlug = $this->resolveProcess($loginLink);

        $definition = LoginLinkProcess::query()->where('slug', $processSlug)->first();

        if ($definition !== null && filled($definition->handler_key)) {
            return (string) $definition->handler_key;
        }

        return $processSlug;
    }

    /**
     * Process discriminator on the link; empty/missing defaults to login (BC).
     */
    protected function resolveProcess(LoginLink $loginLink): string
    {
        $process = $loginLink->process;

        if (is_string($process) && $process !== '') {
            return $process;
        }

        return RedemptionHandlerRegistry::DEFAULT_PROCESS;
    }
}
