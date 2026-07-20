<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

/**
 * Shared Guzzle client options for KoSIT installer artifact downloads.
 */
final class InstallerDownloadHttpOptions
{
    /**
     * @return array<string, mixed>
     */
    public static function guzzle(): array
    {
        return [
            'allow_redirects' => [
                'protocols' => ['https'],
            ],
        ];
    }
}
