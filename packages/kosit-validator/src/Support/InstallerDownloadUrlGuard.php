<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

use RuntimeException;

/**
 * Ensures KoSIT installer download URLs cannot be retargeted to unexpected hosts.
 */
final class InstallerDownloadUrlGuard
{
    public static function assertAllowed(string $url, string $label): void
    {
        if (! str_starts_with($url, 'https://')) {
            throw new RuntimeException("Download URL must use HTTPS: {$url}");
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
            throw new RuntimeException("Download URL is invalid for {$label}: {$url}");
        }

        if (config('kosit-validator.installer.allow_untrusted_download_hosts')) {
            return;
        }

        $host = strtolower((string) $parts['host']);
        /** @var list<string> $allowedHosts */
        $allowedHosts = config('kosit-validator.installer.allowed_download_hosts', []);

        if (! in_array($host, $allowedHosts, true)) {
            throw new RuntimeException(
                "Download host \"{$host}\" is not allowed for {$label}. "
                .'Only GitHub releases from itplr-kosit are permitted.'
            );
        }

        $path = '/'.ltrim((string) $parts['path'], '/');
        /** @var list<string> $allowedPrefixes */
        $allowedPrefixes = config('kosit-validator.installer.allowed_download_path_prefixes', []);

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        throw new RuntimeException(
            "Download URL path is not an allowed itplr-kosit release for {$label}: {$url}"
        );
    }
}
