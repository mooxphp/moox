<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Moox\EBilling\Models\EbillingDocument;
use Moox\Invoice\Models\Invoice;
use Throwable;

/**
 * Identical-content discard feedback:
 * - Credit notes (manual upload): Filament toast for the uploader
 * - Invoices (usually mail ingest): Filament database notifications for admin-panel users
 */
final class IdenticalDuplicateNotifier
{
    private const CREDIT_NOTE_DOCUMENT_TYPE = '381';

    private const ADMIN_PANEL_ID = 'admin';

    private const USER_CACHE_PREFIX = 'ebilling.identical_duplicate.user.';

    private const TOAST_CACHE_PREFIX = 'ebilling.identical_duplicate.toast.';

    private const CACHE_TTL_SECONDS = 86400;

    public function rememberCurrentUser(EbillingDocument $document): void
    {
        $userId = $this->currentUserId();

        if ($userId === null) {
            return;
        }

        Cache::put($this->userCacheKey((string) $document->getKey()), $userId, self::CACHE_TTL_SECONDS);
    }

    public function notifyIdenticalDuplicateDiscarded(
        EbillingDocument $document,
        Invoice $matchedInvoice,
    ): void {
        $rememberedUserId = Cache::pull($this->userCacheKey((string) $document->getKey()));
        $number = $this->documentNumberLabel($matchedInvoice);
        $title = __('e-billing::fields.notification_identical_duplicate_title');
        $body = __('e-billing::fields.notification_identical_duplicate_body', [
            'number' => $number,
        ]);

        if ($this->isCreditNote($matchedInvoice)) {
            $this->queueToastForUser($rememberedUserId, $title, $body);

            return;
        }

        $this->sendInvoiceDatabaseNotifications($rememberedUserId, $title, $body);
    }

    public function flushPendingToastForCurrentUser(): void
    {
        $userId = $this->currentUserId();

        if ($userId === null) {
            return;
        }

        $payload = Cache::pull($this->toastCacheKey($userId));

        if (! is_array($payload)) {
            return;
        }

        $title = $payload['title'] ?? null;
        $body = $payload['body'] ?? null;

        if (! is_string($title) || $title === '') {
            return;
        }

        Notification::make()
            ->title($title)
            ->body(is_string($body) ? $body : null)
            ->warning()
            ->persistent()
            ->send();
    }

    private function isCreditNote(Invoice $matchedInvoice): bool
    {
        return (string) ($matchedInvoice->document_type ?? '') === self::CREDIT_NOTE_DOCUMENT_TYPE;
    }

    private function documentNumberLabel(Invoice $matchedInvoice): string
    {
        if (is_string($matchedInvoice->invoice_number) && $matchedInvoice->invoice_number !== '') {
            return $matchedInvoice->invoice_number;
        }

        return (string) $matchedInvoice->getKey();
    }

    private function queueToastForUser(?string $userId, string $title, string $body): void
    {
        if ($userId === null || $userId === '') {
            return;
        }

        Cache::put($this->toastCacheKey($userId), [
            'title' => $title,
            'body' => $body,
        ], self::CACHE_TTL_SECONDS);
    }

    private function sendInvoiceDatabaseNotifications(?string $rememberedUserId, string $title, string $body): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $recipients = $this->invoiceNotificationRecipients($rememberedUserId);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::make()
            ->title($title)
            ->body($body)
            ->warning()
            ->sendToDatabase($recipients);
    }

    /**
     * @return Collection<int, Model&Authenticatable>
     */
    private function invoiceNotificationRecipients(?string $rememberedUserId): Collection
    {
        $userClass = config('auth.providers.users.model');
        $panel = $this->adminPanel();

        if (
            ! is_string($userClass)
            || ! class_exists($userClass)
            || ! is_subclass_of($userClass, Model::class)
            || ! $panel instanceof Panel
        ) {
            return collect();
        }

        if (is_string($rememberedUserId) && $rememberedUserId !== '') {
            $user = $userClass::query()->find($rememberedUserId);

            if ($this->canAccessAdminPanel($user, $panel)) {
                /** @var Collection<int, Model&Authenticatable> $single */
                $single = collect([$user]);

                return $single;
            }

            return collect();
        }

        /** @var Collection<int, Model&Authenticatable> $users */
        $users = $userClass::query()->get()->filter(
            fn (mixed $user): bool => $this->canAccessAdminPanel($user, $panel),
        )->values();

        return $users;
    }

    private function adminPanel(): ?Panel
    {
        try {
            return Filament::getPanel(self::ADMIN_PANEL_ID);
        } catch (Throwable) {
            return null;
        }
    }

    private function canAccessAdminPanel(mixed $user, Panel $panel): bool
    {
        return $user instanceof Authenticatable
            && $user instanceof Model
            && $user instanceof FilamentUser
            && $user->canAccessPanel($panel);
    }

    private function currentUserId(): ?string
    {
        $user = Auth::user();

        if (! $user instanceof Authenticatable) {
            return null;
        }

        $id = $user->getAuthIdentifier();

        if ($id === null || $id === '') {
            return null;
        }

        return (string) $id;
    }

    private function userCacheKey(string $documentId): string
    {
        return self::USER_CACHE_PREFIX.$documentId;
    }

    private function toastCacheKey(string $userId): string
    {
        return self::TOAST_CACHE_PREFIX.$userId;
    }
}
