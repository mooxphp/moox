<?php

declare(strict_types=1);

namespace Moox\Security\Notifications\Passwords;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PasswordResetNotification extends Notification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $token,
        protected string $panelId,
    ) {}

    public static function forToken(string $token, ?string $panelId = null): self
    {
        return new self($token, $panelId ?? Filament::getCurrentOrDefaultPanel()->getId());
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail(CanResetPassword|Model|Authenticatable $notifiable): MailMessage
    {
        $mailRecipientName = config('security.mail_recipient_name') ?? 'name';

        return (new MailMessage)
            ->subject(__('security::translations.mail_reset_password_subject'))
            ->greeting(__('security::translations.mail_greeting').sprintf(' %s,', (string) data_get($notifiable, $mailRecipientName, '')))
            ->line(__('security::translations.mail_intro'))
            ->action(__('security::translations.mail_action'), $this->resetUrl($notifiable))
            ->line(__('security::translations.mail_expire_prefix').' '.$this->getReadableExpiryTime().'.')
            ->line(__('security::translations.mail_outro'))
            ->salutation(__('security::translations.mail_salutation') . '<br>' . config('mail.from.name'));
    }

    protected function resetUrl(CanResetPassword|Model|Authenticatable $notifiable): string
    {
        return $this->panel()->getResetPasswordUrl($this->token, $notifiable);
    }

    protected function getReadableExpiryTime(): string
    {
        $panel = $this->panel();
        $expiryMinutes = config('auth.passwords.'.$panel->getAuthPasswordBroker().'.expire')
            ?? config('auth.passwords.users.expire');
        $expiryTime = Carbon::now()->addMinutes($expiryMinutes + 1);

        return $expiryTime->diffForHumans();
    }

    protected function panel(): Panel
    {
        return Filament::getPanel($this->panelId);
    }
}
