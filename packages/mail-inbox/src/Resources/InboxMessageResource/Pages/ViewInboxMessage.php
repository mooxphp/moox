<?php

declare(strict_types=1);

namespace Moox\MailInbox\Resources\InboxMessageResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Moox\MailInbox\Enums\InboxAttachmentProcessingStatus;
use Moox\MailInbox\Enums\InboxMessageProcessingStatus;
use Moox\MailInbox\Models\InboxMessage;
use Moox\MailInbox\Resources\InboxMessageResource;
use Moox\MailInbox\Services\MailInboxService;
use Throwable;

final class ViewInboxMessage extends ViewRecord
{
    protected static string $resource = InboxMessageResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return self::getResource()::getEloquentQuery()
            ->with(['attachments'])
            ->whereKey($key)
            ->firstOrFail();
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('retry_failed')
                ->label(__('mail-inbox::fields.action_retry_failed'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('mail-inbox::fields.action_retry_failed_heading'))
                ->modalDescription(__('mail-inbox::fields.action_retry_failed_description'))
                ->visible(fn (): bool => $this->record instanceof InboxMessage
                    && in_array($this->record->processing_status, [
                        InboxMessageProcessingStatus::Failed->value,
                        InboxMessageProcessingStatus::PartiallyFailed->value,
                    ], true))
                ->action(function (): void {
                    $record = $this->record;
                    if (! $record instanceof InboxMessage) {
                        return;
                    }

                    try {
                        app(MailInboxService::class)->retryFailedMessage($record);
                    } catch (Throwable) {
                        Notification::make()
                            ->title(__('mail-inbox::fields.notification_action_failed'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record = $record->fresh(['attachments']);

                    Notification::make()
                        ->title(__('mail-inbox::fields.notification_retry_success'))
                        ->success()
                        ->send();
                }),
            Action::make('reenqueue_processing')
                ->label(__('mail-inbox::fields.action_reenqueue'))
                ->icon(Heroicon::OutlinedPlay)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading(__('mail-inbox::fields.action_reenqueue_heading'))
                ->modalDescription(__('mail-inbox::fields.action_reenqueue_description'))
                ->visible(fn (): bool => $this->record instanceof InboxMessage
                    && $this->record->pdfAttachments()
                        ->where('processing_status', InboxAttachmentProcessingStatus::New->value)
                        ->exists())
                ->action(function (): void {
                    $record = $this->record;
                    if (! $record instanceof InboxMessage) {
                        return;
                    }

                    try {
                        app(MailInboxService::class)->enqueueParseJobsForInboxMessage($record);
                    } catch (Throwable) {
                        Notification::make()
                            ->title(__('mail-inbox::fields.notification_action_failed'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record = $record->fresh(['attachments']);

                    Notification::make()
                        ->title(__('mail-inbox::fields.notification_reenqueue_success'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
