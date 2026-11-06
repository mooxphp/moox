<?php

declare(strict_types=1);

namespace Moox\Media\Support;

/**
 * Interim SVG hardening without a dedicated allowlist library.
 * Prefer a proper sanitizer (e.g. enshrined/svg-sanitize) when adding a Composer dependency is approved.
 */
final class SvgSanitizer
{
    /**
     * Strip scriptable / external content from SVG markup without rewriting the visual structure.
     */
    public function sanitize(string $svg): string
    {
        $svg = preg_replace('/<!DOCTYPE[^>]*>/i', '', $svg) ?? $svg;
        $svg = preg_replace('/<\?xml[^>]*\?>/i', '', $svg) ?? $svg;

        $svg = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $svg) ?? $svg;
        $svg = preg_replace('/<foreignObject\b[^>]*>.*?<\/foreignObject>/is', '', $svg) ?? $svg;

        // Event handlers (onclick, onload, …).
        $svg = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg) ?? $svg;

        // Remove <use> that points at external / scriptable targets (before attr stripping).
        $svg = preg_replace(
            '/<use\b[^>]*\s(?:xlink:)?href\s*=\s*("(?:\s*(?:https?:|data:|blob:|javascript:)[^"]*)"|\'(?:\s*(?:https?:|data:|blob:|javascript:)[^\']*)\')[^>]*\/?>/i',
            '',
            $svg,
        ) ?? $svg;

        // javascript: / data: / blob: / http(s) in href / xlink:href.
        $svg = preg_replace(
            '/\s(?:xlink:)?href\s*=\s*("\s*(?:javascript:|data:|blob:|https?:)[^"]*"|\'\s*(?:javascript:|data:|blob:|https?:)[^\']*\')/i',
            '',
            $svg,
        ) ?? $svg;

        // SMIL that can retarget href to javascript:/data:.
        $svg = preg_replace(
            '/<(?:animate|set)\b[^>]*\sattributeName\s*=\s*("(?:xlink:)?href"|\'(?:xlink:)?href\')[^>]*\/?>/i',
            '',
            $svg,
        ) ?? $svg;
        $svg = preg_replace(
            '/<(?:animate|set)\b[^>]*\sattributeName\s*=\s*("(?:xlink:)?href"|\'(?:xlink:)?href\')[^>]*>.*?<\/(?:animate|set)>/is',
            '',
            $svg,
        ) ?? $svg;

        // Drop style attributes that contain url(...).
        $svg = preg_replace(
            '/\sstyle\s*=\s*("[^"]*url\s*\([^)]*\)[^"]*"|\'[^\']*url\s*\([^)]*\)[^\']*\')/i',
            '',
            $svg,
        ) ?? $svg;

        return trim($svg);
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

        $sanitized = $this->sanitize($contents);
        if ($sanitized === $contents) {
            return true;
        }

        return file_put_contents($path, $sanitized) !== false;
    }
}
