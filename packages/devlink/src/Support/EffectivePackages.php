<?php

declare(strict_types=1);

namespace Moox\Devlink\Support;

final class EffectivePackages
{
    private const LINKABLE_TYPES = ['public', 'local', 'private'];

    private const TRAVERSABLE_TYPES = ['bundle', 'public', 'local', 'private'];

    /**
     * @param  array<string, array<string, mixed>>  $packages
     * @return array<string, array<string, mixed>>
     */
    public static function resolve(string $root, array $packages): array
    {
        $queue = [];
        $visited = [];
        $effective = [];

        foreach ($packages as $name => $package) {
            if ($package['active'] ?? false) {
                $queue[] = $name;
            }
        }

        while ($queue !== []) {
            $slug = array_shift($queue);

            if (isset($visited[$slug])) {
                continue;
            }

            $visited[$slug] = true;

            if (! isset($packages[$slug])) {
                continue;
            }

            $package = $packages[$slug];
            $type = (string) ($package['type'] ?? '');

            if (! in_array($type, self::TRAVERSABLE_TYPES, true)) {
                continue;
            }

            if (in_array($type, self::LINKABLE_TYPES, true)) {
                $effective[$slug] = $package;
            }

            $directory = self::resolvePackageDirectory($root, $package);

            foreach (self::readMooxSlugsFromComposer($directory) as $depSlug) {
                if (isset($packages[$depSlug]) && ! isset($visited[$depSlug])) {
                    $queue[] = $depSlug;
                }
            }
        }

        return $effective;
    }

    /**
     * @param  array<string, array<string, mixed>>  $packages
     * @return list<string>
     */
    public static function resolveSlugs(string $root, array $packages): array
    {
        return array_keys(self::resolve($root, $packages));
    }

    public static function isLinkableType(string $type): bool
    {
        return in_array($type, self::LINKABLE_TYPES, true);
    }

    /**
     * @param  array<string, mixed>  $package
     */
    public static function resolvePackageDirectory(string $root, array $package): ?string
    {
        $path = (string) ($package['path'] ?? '');

        if ($path === '' || str_contains($path, 'disabled')) {
            return null;
        }

        $path = str_replace('../moox/', '', $path);

        if (! str_starts_with($path, '/') && ! preg_match('#^[A-Za-z]:[\\\\/]#', $path)) {
            $full = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
        } else {
            $full = $path;
        }

        $resolved = realpath($full);

        if ($resolved !== false) {
            return $resolved;
        }

        return is_dir($full) ? $full : null;
    }

    public static function readMooxSlugsFromComposer(?string $directory): array
    {
        if ($directory === null) {
            return [];
        }

        $composerJson = $directory.DIRECTORY_SEPARATOR.'composer.json';

        if (! is_readable($composerJson)) {
            return [];
        }

        $contents = file_get_contents($composerJson);

        if ($contents === false) {
            return [];
        }

        $data = json_decode($contents, true);

        if (! is_array($data)) {
            return [];
        }

        $slugs = [];

        foreach (['require', 'require-dev'] as $section) {
            foreach ($data[$section] ?? [] as $name => $_constraint) {
                if (! is_string($name) || ! str_starts_with($name, 'moox/')) {
                    continue;
                }

                $slug = substr($name, 5);

                if ($slug !== '') {
                    $slugs[] = $slug;
                }
            }
        }

        return array_values(array_unique($slugs));
    }
}
