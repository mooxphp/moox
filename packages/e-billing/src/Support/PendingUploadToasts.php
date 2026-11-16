<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Moox\EBilling\Resources\Concerns\FlushesPendingUploadToasts;

/**
 * Standing warning toasts for an uploader about what the pipeline did with an upload in the background, e.g. an
 * identical credit note discarded or a foreign document ignored. Queued per user and shown on the user's next
 * request on a list page ({@see FlushesPendingUploadToasts}).
 */
final class PendingUploadToasts
{
    private const CACHE_PREFIX = 'ebilling.upload_toasts.';

    private const CACHE_TTL_SECONDS = 86400;

    public function push(string $userId, string $title, ?string $body): void
    {
        if ($userId === '') {
            return;
        }

        $key = self::CACHE_PREFIX.$userId;
        $pending = Cache::get($key);
        $pending = is_array($pending) ? $pending : [];
        $pending[] = ['title' => $title, 'body' => $body];

        Cache::put($key, $pending, self::CACHE_TTL_SECONDS);
    }

    public function flushForCurrentUser(): void
    {
        $user = Auth::user();
        $userId = $user instanceof Authenticatable ? (string) $user->getAuthIdentifier() : '';
        if ($userId === '') {
            return;
        }

        $pending = Cache::pull(self::CACHE_PREFIX.$userId);
        if (! is_array($pending)) {
            return;
        }

        foreach ($pending as $toast) {
            if (! is_array($toast) || ! is_string($toast['title'] ?? null) || $toast['title'] === '') {
                continue;
            }

            Notification::make()
                ->title($toast['title'])
                ->body(is_string($toast['body'] ?? null) ? $toast['body'] : null)
                ->warning()
                ->persistent()
                ->send();
        }
    }
}
