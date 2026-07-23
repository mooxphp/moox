<?php

namespace Moox\UserDevice\Notifications;

use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Moox\UserDevice\Resources\UserDeviceResource;

class NewDeviceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(protected $deviceDetails)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(__('user-device::translations.mail_subject_new_device'))
            ->view('user-device::mail.new-device', [
                'notifiable' => $notifiable,
                'deviceTitle' => $this->deviceDetails['title'] ?? null,
                'deviceIp' => $this->deviceDetails['ip_address'] ?? null,
                'devicePlatform' => $this->deviceDetails['platform'] ?? null,
                'deviceBrowser' => $this->deviceDetails['browser'] ?? null,
                'deviceOs' => $this->deviceDetails['os'] ?? null,
                'deviceCity' => $this->deviceDetails['city'] ?? null,
                'deviceCountry' => $this->deviceDetails['country'] ?? null,
                'reviewUrl' => $this->getReviewDevicesUrl(),
                'trustUrl' => $this->getTrustUrl($notifiable),
                'logoUrl' => $this->getLogoUrl(),
            ]);
    }

    protected function getReviewDevicesUrl(): string
    {
        $panelId = $this->deviceDetails['panel_id'] ?? null;

        if (filled($panelId) && class_exists(Filament::class)) {
            $relativeUrl = UserDeviceResource::getUrl('index', panel: $panelId);

            return url($relativeUrl);
        }

        return url(UserDeviceResource::getUrl('index'));
    }

    protected function getLogoUrl(): string
    {
        $configuredUrl = config('user-device.mail_logo_url');

        if (filled($configuredUrl)) {
            $configuredUrl = (string) $configuredUrl;

            if (str_starts_with($configuredUrl, '/')) {
                return url($configuredUrl);
            }

            return $configuredUrl;
        }

        return 'https://laravel.com/img/logomark.min.svg';
    }

    protected function getTrustUrl(mixed $notifiable): ?string
    {
        $panelId = (string) ($this->deviceDetails['panel_id'] ?? '');
        $deviceId = $this->deviceDetails['device_id'] ?? null;

        if (blank($panelId) || blank($deviceId)) {
            return null;
        }

        $expires = now()->addMinutes((int) config('user-device.trust_link_expires_minutes', 60));

        return URL::temporarySignedRoute(
            'user-device.devices.trust',
            $expires,
            ['panel' => $panelId, 'device' => $deviceId],
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        return [
            'deviceDetails' => $this->deviceDetails,
        ];
    }
}
