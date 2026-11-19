<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use enshrined\svgSanitize\Sanitizer;
use RuntimeException;

/**
 * Sanitize untrusted SVG uploads via enshrined/svg-sanitize (DOM allowlist).
 */
final class SvgSanitizer
{
    public function __construct(
        private readonly Sanitizer $sanitizer = new Sanitizer,
    ) {
        $this->sanitizer->removeRemoteReferences(true);
        $this->sanitizer->removeXMLTag(true);
        $this->sanitizer->minify(true);
    }

    /**
     * Strip scriptable / remote content from SVG markup.
     *
     * @throws RuntimeException When the markup cannot be parsed as SVG.
     */
    public function sanitize(string $svg): string
    {
        $clean = $this->sanitizer->sanitize($svg);

        if ($clean === false) {
            throw new RuntimeException('SVG could not be sanitized.');
        }

        return trim($clean);
    }

    public function sanitizeFile(string $path): bool
    {
        if (! is_file($path)) {
            return false;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return false;
        }

        try {
            $sanitized = $this->sanitize($contents);
        } catch (RuntimeException) {
            return false;
        }

        if ($sanitized === $contents) {
            return true;
        }

        return file_put_contents($path, $sanitized) !== false;
    }
}
