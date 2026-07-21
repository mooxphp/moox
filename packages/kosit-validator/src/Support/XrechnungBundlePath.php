<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

/**
 * Resolves the on-disk path of the pinned XRechnung configuration bundle archive.
 */
final class XrechnungBundlePath
{
    public const BUNDLE_FILENAME = '.xrechnung-bundle.zip';

    public static function resolve(KositInstallPaths $paths): string
    {
        return $paths->xrechnungDir.'/'.self::BUNDLE_FILENAME;
    }
}
