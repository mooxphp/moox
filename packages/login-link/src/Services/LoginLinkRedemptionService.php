<?php

namespace Moox\LoginLink\Services;

use Filament\Models\Contracts\FilamentUser;
use Filament\PanelRegistry;
use Illuminate\Support\Facades\DB;
use Moox\LoginLink\Models\LoginLink;

class LoginLinkRedemptionService
{
    public function redeem(int|string $loginLinkId, string $panelId): mixed
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

            $user = $loginLink->user()->first();
            if (! $user) {
                return null;
            }

            if (! $this->userCanAccessPanel($user, $panelId)) {
                return null;
            }

            $loginLink->update(['used_at' => now()]);

            return $user;
        });
    }

    private function userCanAccessPanel(mixed $user, string $panelId): bool
    {
        if (! $user instanceof FilamentUser) {
            return false;
        }

        $panel = app(PanelRegistry::class)->get($panelId);
        if (! $panel) {
            return false;
        }

        return $user->canAccessPanel($panel);
    }
}
