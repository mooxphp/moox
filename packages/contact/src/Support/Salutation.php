<?php

declare(strict_types=1);

namespace Moox\Contact\Support;

final class Salutation
{
    /** @return array<string, string> */
    public static function options(): array
    {
        /** @var list<string> $codes */
        $codes = config('contact.salutation_codes', ['none']);

        return collect($codes)
            ->mapWithKeys(fn (string $code): array => [$code => __("contact::salutations.{$code}")])
            ->all();
    }
}
