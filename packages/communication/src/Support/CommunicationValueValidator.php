<?php

declare(strict_types=1);

namespace Moox\Communication\Support;

use Moox\Communication\Enums\CommunicationValidationType;

/**
 * Turns a raw communication value into the canonical form for its type.
 * A national phone number needs the owner's calling code. Nothing is guessed.
 */
final class CommunicationValueValidator
{
    public function canonicalize(string $validationType, mixed $raw, ?string $callingCode = null): ?string
    {
        $type = CommunicationValidationType::tryFrom($validationType);
        if (! $type instanceof CommunicationValidationType) {
            return null;
        }

        $value = is_scalar($raw) ? trim((string) $raw) : '';
        if ($value === '') {
            return null;
        }

        return match ($type) {
            CommunicationValidationType::Email => $this->email($value),
            CommunicationValidationType::Phone, CommunicationValidationType::Fax => $this->phone($value, $callingCode),
            CommunicationValidationType::Url => $this->url($value),
        };
    }

    private function email(string $value): ?string
    {
        if (str_contains($value, ';') || str_contains($value, ',')) {
            return null;
        }

        $email = mb_strtolower(preg_replace('/\s+/', '', $value) ?? '');
        if (substr_count($email, '@') !== 1 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $email;
    }

    private function phone(string $value, ?string $callingCode): ?string
    {
        $value = preg_replace('/^(?:telefon|tel|phone|fax)\s*[:.]?\s*/iu', '', $value) ?? $value;
        $value = str_replace(["\u{2013}", "\u{2014}", "\u{2212}"], '-', $value);
        $value = preg_replace('/\([^)]*\p{L}[^)]*\)/u', '', $value) ?? $value;

        if ($value === '' || str_contains($value, '@') || str_contains($value, ';') || str_contains($value, ',') || preg_match('/\p{L}/u', $value) === 1) {
            return null;
        }

        $value = preg_replace('/\(0\)/', '', $value) ?? $value;
        $value = preg_replace('/[^\d+]/', '', $value) ?? '';
        if (str_starts_with($value, '00')) {
            $value = '+'.substr($value, 2);
        }

        if (str_starts_with($value, '+')) {
            $digits = substr($value, 1);
        } elseif (str_starts_with($value, '0')) {
            $callingDigits = $this->callingDigits($callingCode);
            if ($callingDigits === null) {
                return null;
            }

            $digits = $callingDigits.ltrim(substr($value, 1), '0');
        } else {
            return null;
        }

        if (preg_match('/^[1-9]\d{7,14}$/', $digits) !== 1) {
            return null;
        }

        return '+'.$digits;
    }

    private function url(string $value): ?string
    {
        if (preg_match('/\s/', $value) === 1) {
            return null;
        }

        if (preg_match('#^https?://#i', $value) !== 1) {
            $value = 'https://'.$value;
        }

        $value = rtrim($value, '/');
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($value);
        if (! is_array($parts) || ! is_string($parts['host'] ?? null) || $parts['host'] === '') {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $host = strtolower($parts['host']);
        $path = is_string($parts['path'] ?? null) ? $parts['path'] : '';
        $query = is_string($parts['query'] ?? null) && $parts['query'] !== '' ? '?'.$parts['query'] : '';

        return $scheme.'://'.$host.$path.$query;
    }

    private function callingDigits(?string $callingCode): ?string
    {
        if ($callingCode === null) {
            return null;
        }

        $digits = ltrim(preg_replace('/\D/', '', $callingCode) ?? '', '0');

        return $digits === '' ? null : $digits;
    }
}
