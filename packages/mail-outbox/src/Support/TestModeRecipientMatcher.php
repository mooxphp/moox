<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

use Illuminate\Support\Str;

final class TestModeRecipientMatcher
{
    /**
     * @param  list<string>  $patterns
     */
    public function matches(string $email, array $patterns): bool
    {
        $normalized = strtolower(trim($email));

        if ($normalized === '') {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (! is_string($pattern) || $pattern === '') {
                continue;
            }

            if (Str::is(strtolower($pattern), $normalized)) {
                return true;
            }
        }

        return false;
    }
}
