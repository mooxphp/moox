<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Verifies the pinned XRechnung bundle and runs validation against a private temp extract.
 */
final class XrechnungExecutionGuard
{
    /**
     * @template T
     *
     * @param  callable(string $scenariosPath, string $repositoryPath): T  $run
     * @return T
     */
    public static function withVerifiedExtractedDir(
        KositInstallPaths $paths,
        string $expectedSha256,
        callable $run,
    ): mixed {
        $bundlePath = XrechnungBundlePath::resolve($paths);

        if (! is_file($bundlePath) || ! is_readable($bundlePath)) {
            throw new RuntimeException(
                'XRechnung configuration bundle not found at '.$bundlePath.'. Run php artisan kosit:install --force.'
            );
        }

        $zipBytes = file_get_contents($bundlePath);

        if ($zipBytes === false) {
            throw new RuntimeException("Cannot verify checksum; failed to read: {$bundlePath}");
        }

        InstallerChecksum::assertValidBytes(
            $zipBytes,
            $expectedSha256,
            'XRechnung configuration bundle',
        );

        $workspace = sys_get_temp_dir().'/kosit-xrechnung-'.uniqid('', true);
        $extractDir = $workspace.'/extracted';
        $tempZip = $workspace.'/bundle.zip';

        File::ensureDirectoryExists($workspace);

        try {
            if (file_put_contents($tempZip, $zipBytes) === false) {
                throw new RuntimeException('Cannot write temporary XRechnung bundle.');
            }

            SafeZipExtractor::extract($tempZip, $extractDir);

            $scenariosPath = RecursiveFileFinder::find($extractDir, 'scenarios.xml');

            if ($scenariosPath === null) {
                throw new RuntimeException('No scenarios.xml found in verified XRechnung configuration bundle.');
            }

            return $run($scenariosPath, dirname($scenariosPath));
        } finally {
            File::deleteDirectory($workspace);
        }
    }
}
