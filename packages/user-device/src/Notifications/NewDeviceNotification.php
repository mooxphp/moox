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
     * @param  array<string, mixed>  $deviceDetails
     */
    public function __construct(protected array $deviceDetails)
    {
    }

    /**
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $subject = __('user-device::translations.mail_subject_new_device');
        $data = $this->mailTemplateData($notifiable);

        $html = $this->renderMailTemplate($data);

        if (is_string($html) && $html !== '') {
            return (new MailMessage)
                ->subject($subject)
                ->view('user-device::mail.raw-html', ['html' => $html]);
        }

        return (new MailMessage)
            ->subject($subject)
            ->view('user-device::mail.new-device', [
                'notifiable' => $notifiable,
                'deviceTitle' => $this->deviceDetails['title'] ?? null,
                'deviceIp' => $this->deviceDetails['ip_address'] ?? null,
                'devicePlatform' => $this->deviceDetails['platform'] ?? null,
                'deviceBrowser' => $this->deviceDetails['browser'] ?? null,
                'deviceOs' => $this->deviceDetails['os'] ?? null,
                'deviceCity' => $this->deviceDetails['city'] ?? null,
                'deviceCountry' => $this->deviceDetails['country'] ?? null,
                'reviewUrl' => $data['reviewUrl'],
                'trustUrl' => $data['magicLink'],
                'logoUrl' => $this->getLogoUrl(),
                'enforceTrust' => (bool) config('user-device.enforce_trust', true),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function mailTemplateData(mixed $notifiable): array
    {
        $headline = __('user-device::translations.mail_title_new_device');
        $reviewUrl = $this->getReviewDevicesUrl();
        $trustUrl = config('user-device.enforce_trust', true)
            ? $this->getTrustUrl($notifiable)
            : null;
        $expiresMinutes = (int) config('user-device.trust_link_expires_minutes', 60);

        return [
            'title' => $headline,
            'headline' => $headline,
            'displayName' => $this->displayName($notifiable),
            'email' => $this->emailAddress($notifiable),
            'user' => $notifiable,
            'subject' => $notifiable,
            'deviceTitle' => (string) ($this->deviceDetails['title'] ?? ''),
            'deviceSystem' => collect([
                $this->deviceDetails['platform'] ?? null,
                $this->deviceDetails['browser'] ?? null,
                $this->deviceDetails['os'] ?? null,
            ])->filter()->implode(' · '),
            'deviceIp' => (string) ($this->deviceDetails['ip_address'] ?? ''),
            'deviceLocation' => collect([
                $this->deviceDetails['city'] ?? null,
                $this->deviceDetails['country'] ?? null,
            ])->filter()->implode(', '),
            // Notify-only: never fall back to reviewUrl (heco templates omit CTA).
            'magicLink' => config('user-device.enforce_trust', true)
                ? ($trustUrl ?? $reviewUrl)
                : '',
            'reviewUrl' => $reviewUrl,
            'expiresMinutes' => $expiresMinutes,
        ];
    }

    protected function emailAddress(mixed $notifiable): string
    {
        return trim((string) data_get($notifiable, 'email', ''));
    }

    /**
     * Soft-couple to moox/mail-template when available (no hard composer require).
     *
     * @param  array<string, mixed>  $data
     */
    protected function renderMailTemplate(array $data): ?string
    {
        $bridge = 'Moox\\MailTemplate\\Support\\MailTemplateBridge';

        if (! class_exists($bridge) || ! $bridge::isAvailable()) {
            return null;
        }

        $slug = trim((string) config('user-device.mail_template_slug', 'new-device'));

        if ($slug === '') {
            return null;
        }

        $html = $bridge::toHtmlBySlug($slug, $data);

        return is_string($html) && $html !== '' ? $html : null;
    }

    protected function displayName(mixed $notifiable): string
    {
        $firstName = trim((string) data_get($notifiable, 'first_name', ''));
        $lastName = trim((string) data_get($notifiable, 'last_name', ''));
        $fullName = trim($firstName.' '.$lastName);

        if ($fullName !== '') {
            return $fullName;
        }

        return trim((string) data_get($notifiable, 'name', data_get($notifiable, 'display_name', '')));
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
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'deviceDetails' => $this->deviceDetails,
        ];
    }
}
