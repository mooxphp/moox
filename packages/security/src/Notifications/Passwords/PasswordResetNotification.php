<?php

namespace Moox\Security\Notifications\Passwords;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\HtmlString;

class PasswordResetNotification extends Notification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $token;

    protected Panel $panel;

    public function __construct($token)
    {
        $this->token = $token;
        $this->panel = Filament::getCurrentPanel();
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mailRecipientName = config('security.mail_recipient_name') ?? 'name';

        return (new MailMessage)
            ->subject(__('security::translations.Reset Password Message'))
            ->greeting(__('security::translations.Hello')." {$notifiable->$mailRecipientName},")
            ->line(__('security::translations.You are receiving this email because we received a password reset request for your account.'))
            ->action(__('security::translations.Reset Password'), $this->resetUrl($notifiable))
            ->line(__('security::translations.This password reset link will expire').' '.$this->getReadableExpiryTime().'.')
            ->line(__('security::translations.If you did not request a password reset, no further action is required.'))
            ->salutation(new HtmlString(__('security::translations.Regards').'<br>'.config('mail.from.name')));
    }

    protected function resetUrl($notifiable): string
    {
        return $this->panel->getResetPasswordUrl($this->token, $notifiable);
    }

    protected function getReadableExpiryTime(): string
    {
        $expiryMinutes = config('auth.passwords.'.$this->panel->getAuthPasswordBroker().'.expire') ?? config('auth.passwords.users.expire');
        $expiryTime = Carbon::now()->addMinutes($expiryMinutes + 1);

        return $expiryTime->diffForHumans();
    }
}
