<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Resources\MailSendLogResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Moox\MailOutbox\Enums\MailSendStatus;
use Moox\MailOutbox\Exceptions\CannotResendMailException;
use Moox\MailOutbox\Models\MailSendLog;
use Moox\MailOutbox\Resources\MailSendLogResource;
use Moox\MailOutbox\Support\ResendMailService;
use Throwable;

final class ViewMailSendLog extends ViewRecord
{
    protected static string $resource = MailSendLogResource::class;

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            MailSendLogResource::getPluralModelLabel(),
            $this->getBreadcrumb(),
        ];
    }

    protected function resolveRecord(int|string $key): Model
    {
        return self::getResource()::getEloquentQuery()
            ->with(['related'])
            ->whereKey($key)
            ->firstOrFail();
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_raw_message')
                ->label(__('mail-outbox::fields.action_view_raw_message'))
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading(__('mail-outbox::fields.action_view_raw_message_heading'))
                ->modalDescription(__('mail-outbox::fields.action_view_raw_message_description'))
                ->modalContent(fn (): View => view('mail-outbox::raw-message-modal', [
                    'content' => $this->record instanceof MailSendLog ? (string) $this->record->raw_message : '',
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('mail-outbox::fields.action_close'))
                ->visible(fn (): bool => $this->record instanceof MailSendLog
                    && $this->record->status === MailSendStatus::Sent
                    && filled($this->record->raw_message)),
            Action::make('resend')
                ->label(__('mail-outbox::fields.action_resend'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('mail-outbox::fields.action_resend_heading'))
                ->modalDescription(__('mail-outbox::fields.action_resend_description'))
                ->visible(fn (): bool => $this->record instanceof MailSendLog
                    && app(ResendMailService::class)->canResend($this->record))
                ->action(function (): void {
                    $record = $this->record;

                    if (! $record instanceof MailSendLog) {
                        return;
                    }

                    try {
                        app(ResendMailService::class)->resend($record);
                    } catch (CannotResendMailException) {
                        Notification::make()
                            ->title(__('mail-outbox::fields.notification_resend_unavailable'))
                            ->danger()
                            ->send();

                        return;
                    } catch (Throwable) {
                        Notification::make()
                            ->title(__('mail-outbox::fields.notification_action_failed'))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('mail-outbox::fields.notification_resend_success'))
                        ->success()
                        ->send();
                }),
        ];
    }
}

