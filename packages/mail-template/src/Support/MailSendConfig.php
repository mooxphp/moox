<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Support;

use Illuminate\Support\Facades\Schema;
use Moox\Localization\Models\Localization;

final class MailSendConfig
{
    /**
     * @return list<array{name: string, email: string}>
     */
    public static function recipients(): array
    {
        $recipients = config('mail-send.recipients', []);

        if (! is_array($recipients)) {
            return [];
        }

        $normalized = [];

        foreach ($recipients as $recipient) {
            if (! is_array($recipient)) {
                continue;
            }

            $email = strtolower(trim((string) ($recipient['email'] ?? '')));
            $name = trim((string) ($recipient['name'] ?? ''));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'email' => $email,
            ];
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    public static function recipientOptions(): array
    {
        $options = [];

        foreach (self::recipients() as $recipient) {
            $label = $recipient['name'] !== ''
                ? $recipient['name'].' ('.$recipient['email'].')'
                : $recipient['email'];

            $options[$recipient['email']] = $label;
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public static function localeOptions(): array
    {
        $fromLocalizations = self::optionsFromLocalizations();

        if ($fromLocalizations !== []) {
            return $fromLocalizations;
        }

        return self::normalizeLocaleOptions(config('mail-send.locales', [
            'de' => 'Deutsch',
            'en' => 'English',
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public static function viewData(): array
    {
        $data = config('mail-send.view_data', []);

        return is_array($data) ? $data : [];
    }

    /**
     * @param  list<string>  $emails
     * @return list<array{name: string, email: string}>
     */
    public static function allowedRecipients(array $emails): array
    {
        $selected = [];

        foreach ($emails as $email) {
            $selected[strtolower(trim((string) $email))] = true;
        }

        return array_values(array_filter(
            self::recipients(),
            fn (array $recipient): bool => isset($selected[$recipient['email']]),
        ));
    }

    /**
     * @return array<string, string>
     */
    private static function optionsFromLocalizations(): array
    {
        if (! class_exists(Localization::class)) {
            return [];
        }

        try {
            if (! Schema::hasTable('localizations')) {
                return [];
            }

            $localizations = Localization::query()
                ->where('is_active_admin', true)
                ->orderByDesc('is_default')
                ->orderBy('title')
                ->get(['locale_variant', 'title']);
        } catch (\Throwable) {
            return [];
        }

        $options = [];

        foreach ($localizations as $localization) {
            $code = trim((string) $localization->locale_variant);
            $label = trim((string) $localization->title);

            if ($code === '' || $label === '') {
                continue;
            }

            $options[$code] = $label;
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private static function normalizeLocaleOptions(mixed $locales): array
    {
        if (! is_array($locales)) {
            return [];
        }

        $options = [];

        foreach ($locales as $code => $label) {
            $code = trim((string) $code);
            $label = trim((string) $label);

            if ($code === '' || $label === '') {
                continue;
            }

            $options[$code] = $label;
        }

        return $options;
    }
}
