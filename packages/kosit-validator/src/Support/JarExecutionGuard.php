<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

use RuntimeException;

/**
 * Verifies a validator JAR in memory and executes a callback against a private temp copy.
 *
 * Narrows the TOCTOU window between checksum verification and JVM execution: Java runs
 * bytes that were already hashed, not a second read of a mutable install path.
 */
final class JarExecutionGuard
{
    /**
     * @template T
     *
     * @param  callable(string): T  $run  Receives the absolute path of the verified temp JAR.
     * @return T
     */
    public static function withVerifiedTempCopy(string $jarPath, string $expectedSha256, callable $run): mixed
    {
        if (! is_file($jarPath) || ! is_readable($jarPath)) {
            throw new RuntimeException("Cannot verify checksum; file is missing or unreadable: {$jarPath}");
        }

        $content = file_get_contents($jarPath);

        if ($content === false) {
            throw new RuntimeException("Cannot verify checksum; failed to read: {$jarPath}");
        }

        InstallerChecksum::assertValidBytes(
            $content,
            $expectedSha256,
            'validator JAR',
        );

        $tempPath = tempnam(sys_get_temp_dir(), 'kosit-jar-');

        if ($tempPath === false) {
            throw new RuntimeException('Cannot create temporary validator JAR path.');
        }

        try {
            if (file_put_contents($tempPath, $content) === false) {
                throw new RuntimeException('Cannot write temporary validator JAR.');
            }

            chmod($tempPath, 0500);

            return $run($tempPath);
        } finally {
            @unlink($tempPath);
        }
    }
}
