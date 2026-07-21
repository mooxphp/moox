<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

use RuntimeException;

/**
 * Verifies a KoSIT artifact against a pinned SHA-256 digest at install and runtime.
 */
final class InstallerChecksum
{
    public const CONTEXT_INSTALL = 'install';

    public const CONTEXT_RUNTIME = 'runtime';

    /**
     * Assert that $filePath's SHA-256 matches the expected lowercase hex digest.
     *
     * @param  self::CONTEXT_*  $context
     *
     * @throws RuntimeException When the pin is missing/malformed, the file is unreadable, or digests differ.
     */
    public static function assertValid(
        string $filePath,
        string $expectedSha256,
        string $context = self::CONTEXT_INSTALL,
    ): void {
        $expected = strtolower(trim($expectedSha256));

        if ($expected === '' || preg_match('/^[a-f0-9]{64}$/', $expected) !== 1) {
            throw new RuntimeException(
                'Pinned SHA-256 checksum is not configured. Set kosit-validator.validator.sha256 / kosit-validator.xrechnung.sha256 (or KOSIT_VALIDATOR_SHA256 / KOSIT_XRECHNUNG_SHA256) to the digest of the release asset.'
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
            $basename = basename($filePath);
            $message = "SHA-256 checksum mismatch for {$basename} (expected {$expected}, got {$actual}).";

            if ($context === self::CONTEXT_RUNTIME) {
                $message .= ' Validation aborted.';
            } else {
                $message .= ' Aborting; no files installed.';
            }

            throw new RuntimeException($message);
        }
    }
}
