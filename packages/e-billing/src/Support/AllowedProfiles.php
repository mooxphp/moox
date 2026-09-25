<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use InvalidArgumentException;
use Moox\EBilling\Formats\Exceptions\InvalidFormatPreferenceException;

final class AllowedProfiles
{
    /**
     * @return list<string>
     */
    private static function list(): array
    {
        /** @var list<string> $allowed */
        $allowed = config('e-billing.allowed_profiles', ['EN16931']);

        return $allowed;
    }

    private static function contains(string $profile): bool
    {
        return in_array($profile, self::list(), true);
    }

    public static function assertContains(string $profile, string $format): void
    {
        if (! self::contains($profile)) {
            throw new InvalidFormatPreferenceException(
                "Profile [{$profile}] is not allowed for format [{$format}]. "
                .'Allowed: '.implode(', ', self::list()).'.'
            );
        }
    }

    public static function hybridDefault(): string
    {
        $profile = (string) config('e-billing.default.profile', 'EN16931');

        if ($profile === '' || ! self::contains($profile)) {
            throw new InvalidArgumentException(
                "e-billing.default.profile [{$profile}] must be one of allowed_profiles: "
                .implode(', ', self::list()).'.'
            );
        }

        return $profile;
    }
}
