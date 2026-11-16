<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\UploadedPdfSource;

/**
 * Tells the uploader through a Filament database notification that an upload was ignored as foreign,
 * so the original PDF still gets sent the usual way.
 */
final class ForeignUploadNotifier
{
    public function notify(EbillingDocument $document): void
    {
        $source = $document->source;
        $userClass = config('auth.providers.users.model');

        if (! $source instanceof UploadedPdfSource
            || ! is_string($source->uploader_user_id)
            || $source->uploader_user_id === ''
            || ! is_string($userClass)
            || ! is_subclass_of($userClass, Model::class)
            || ! Schema::hasTable('notifications')) {
            return;
        }

        $uploader = $userClass::query()->find($source->uploader_user_id);
        if ($uploader === null) {
            return;
        }

        $billData = is_array($document->bill_data) ? $document->bill_data : [];
        $number = is_string($billData['invoice_number'] ?? null) && $billData['invoice_number'] !== ''
            ? $billData['invoice_number']
            : (string) $source->original_filename;

        Notification::make()
            ->title(__('e-billing::fields.notification_foreign_upload_title'))
            ->body(__('e-billing::fields.notification_foreign_upload_body', [
                'number' => $number,
                'country' => $document->ignored_reason['country'] ?? '?',
            ]))
            ->warning()
            ->sendToDatabase($uploader);
    }
}
