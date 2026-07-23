<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

use RuntimeException;

/**
 * Verifies a downloaded KoSIT install artifact against a pinned SHA-256 digest.
 */
final class InstallerChecksum
{
    private const MISSING_CHECKSUM_MESSAGE =
        'Installer checksum is not configured. '
        .'Set kosit-validator.validator.sha256 / kosit-validator.xrechnung.sha256 '
        .'(or KOSIT_VALIDATOR_SHA256 / KOSIT_XRECHNUNG_SHA256) '
        .'to the SHA-256 of the pinned release asset.';

    /**
     * Assert that $filePath's SHA-256 matches the expected lowercase hex digest.
     *
     * @throws RuntimeException When the pin is missing/malformed, the file is unreadable, or digests differ.
     */
    public static function assertValid(
        string $filePath,
        string $expectedSha256,
        string $abortMessage = 'Aborting; no files installed.',
    ): void {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new RuntimeException("Cannot verify checksum; file is missing or unreadable: {$filePath}");
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new RuntimeException("Cannot verify checksum; failed to read: {$filePath}");
        }

        self::assertValidBytes($content, $expectedSha256, $abortMessage);
    }

    /**
     * Assert that in-memory bytes match the expected lowercase hex digest.
     *
     * @throws RuntimeException When the pin is missing/malformed or digests differ.
     */
    public static function assertValidBytes(
        string $bytes,
        string $expectedSha256,
        string $abortMessage = 'Aborting validation.',
    ): void {
        $expected = self::normalizeExpected($expectedSha256);
        $actual = hash('sha256', $bytes);

        if ($actual === false) {
            throw new RuntimeException('Cannot verify checksum; failed to hash artifact bytes.');
        }

        if (! hash_equals($expected, strtolower($actual))) {
            throw new RuntimeException(
                'Installer checksum mismatch (expected '.$expected.', got '.$actual.'). '.$abortMessage
            );
        }
    }

    private static function normalizeExpected(string $expectedSha256): string
    {
        $expected = strtolower(trim($expectedSha256));

        if ($expected === '' || preg_match('/^[a-f0-9]{64}$/', $expected) !== 1) {
            throw new RuntimeException(self::MISSING_CHECKSUM_MESSAGE);
        }

        return $expected;
    }
}
