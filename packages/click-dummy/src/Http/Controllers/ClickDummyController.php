<?php

declare(strict_types=1);

namespace Moox\ClickDummy\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ClickDummyController
{
    public function __invoke(Request $request, ?string $path = null): BinaryFileResponse|Response
    {
        $root = $this->resolvedRoot();

        if ($root === null) {
            abort(404);
        }

        $absolutePath = $this->resolveFilePath($root, $path);

        if ($absolutePath === null) {
            abort(404);
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $allowed = config('click-dummy.allowed_extensions', []);

        if (! is_array($allowed) || ! in_array($extension, $allowed, true)) {
            abort(404);
        }

        if (in_array($extension, ['html', 'htm'], true)) {
            return $this->htmlResponse($absolutePath, $path);
        }

        $mimeType = File::mimeType($absolutePath) ?: 'application/octet-stream';

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function htmlResponse(string $absolutePath, ?string $path): Response
    {
        $html = File::get($absolutePath);
        $baseHref = $this->baseHrefForPath($path);

        if (! str_contains($html, '<base ')) {
            $baseTag = '<base href="'.e($baseHref).'">';

            if (preg_match('/<head([^>]*)>/i', $html) === 1) {
                $html = preg_replace('/<head([^>]*)>/i', '<head$1>'.$baseTag, $html, 1) ?? $html;
            } else {
                $html = $baseTag.$html;
            }
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Directory URL under the clickdummy prefix, always with trailing slash.
     * Needed so relative asset URLs resolve when the page is opened without a trailing slash
     * (e.g. /clickdummy → browser would otherwise resolve assets against /).
     */
    private function baseHrefForPath(?string $path): string
    {
        $prefix = trim((string) config('click-dummy.route_prefix', 'clickdummy'), '/');
        $relative = trim(str_replace('\\', '/', (string) $path), '/');

        if ($relative === '' || $relative === 'index.html') {
            return '/'.$prefix.'/';
        }

        if (str_ends_with($relative, '/index.html')) {
            $relative = substr($relative, 0, -strlen('/index.html'));
        } elseif (str_ends_with($relative, 'index.html')) {
            $relative = dirname($relative);
        } elseif (pathinfo($relative, PATHINFO_EXTENSION) !== '') {
            $relative = dirname($relative);
        }

        if ($relative === '.' || $relative === '') {
            return '/'.$prefix.'/';
        }

        return '/'.$prefix.'/'.trim($relative, '/').'/';
    }

    private function resolvedRoot(): ?string
    {
        $configured = config('click-dummy.path');

        if (! is_string($configured) || $configured === '') {
            return null;
        }

        if (! is_dir($configured)) {
            return null;
        }

        $root = realpath($configured);

        return $root === false ? null : $this->normalizePath($root);
    }

    private function resolveFilePath(string $root, ?string $path): ?string
    {
        $relative = $this->normalizeRelativePath($path);

        if ($relative === null) {
            return null;
        }

        $candidate = $relative === ''
            ? $root
            : $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

        if (is_dir($candidate)) {
            $candidate = $candidate.DIRECTORY_SEPARATOR.'index.html';
        }

        $realPath = realpath($candidate);

        if ($realPath === false || ! is_file($realPath)) {
            return null;
        }

        $normalizedFile = $this->normalizePath($realPath);

        if (! $this->isWithinRoot($root, $normalizedFile)) {
            return null;
        }

        return $realPath;
    }

    /**
     * @return string|null Null when the path is unsafe; empty string for the root.
     */
    private function normalizeRelativePath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return '';
        }

        $normalized = str_replace('\\', '/', $path);
        $normalized = ltrim($normalized, '/');

        if ($normalized === '') {
            return '';
        }

        if (str_contains($normalized, "\0")) {
            return null;
        }

        $segments = [];

        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                return null;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private function isWithinRoot(string $root, string $path): bool
    {
        return $path === $root || str_starts_with($path, $root.'/');
    }

    private function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
