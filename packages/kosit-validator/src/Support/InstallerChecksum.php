<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

use RuntimeException;

/**
 * Verifies a downloaded KoSIT install artifact against a pinned SHA-256 digest.
 */
final class InstallerChecksum
{
    /**
     * Assert that $filePath's SHA-256 matches the expected lowercase hex digest.
     *
     * @throws RuntimeException When the pin is missing/malformed, the file is unreadable, or digests differ.
     */
    public static function assertMatches(string $filePath, string $expectedSha256): void
    {
        $expected = strtolower(trim($expectedSha256));

        if ($expected === '' || preg_match('/^[a-f0-9]{64}$/', $expected) !== 1) {
            throw new RuntimeException(
                'Installer checksum is not configured. Set kosit-validator.validator.sha256 / kosit-validator.xrechnung.sha256 (or KOSIT_VALIDATOR_SHA256 / KOSIT_XRECHNUNG_SHA256) to the SHA-256 of the pinned release asset.'
            );
        }

        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException("Cannot verify checksum; file is missing or unreadable: {$filePath}");
        }

        $actual = hash_file('sha256', $filePath);

        if ($actual === false) {
            throw new RuntimeException("Cannot verify checksum; failed to hash: {$filePath}");
        }

        if (! hash_equals($expected, strtolower($actual))) {
            throw new RuntimeException(
                'Installer checksum mismatch (expected '.$expected.', got '.$actual.'). Aborting; no files installed.'
            );
        }
    }
}
