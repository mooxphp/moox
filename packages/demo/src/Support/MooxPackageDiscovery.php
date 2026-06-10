<?php

declare(strict_types=1);

namespace Moox\Demo\Support;

use Illuminate\Support\Facades\File;
use Moox\Core\MooxServiceProvider;

final class MooxPackageDiscovery
{
    /**
     * @return list<string>
     */
    public static function mooxPackageNames(?string $basePath = null): array
    {
        $basePath ??= base_path();
        $names = [];

        $lockPath = $basePath.DIRECTORY_SEPARATOR.'composer.lock';
        if (File::exists($lockPath)) {
            $lock = json_decode(File::get($lockPath), true);
            $packages = array_merge(
                $lock['packages'] ?? [],
                $lock['packages-dev'] ?? []
            );
            foreach ($packages as $pkg) {
                $name = $pkg['name'] ?? null;
                if ($name && str_starts_with($name, 'moox/')) {
                    $names[$name] = true;
                }
            }
        }

        if ($names === []) {
            $composerPath = $basePath.DIRECTORY_SEPARATOR.'composer.json';
            if (File::exists($composerPath)) {
                $composer = json_decode(File::get($composerPath), true);
                $allPackages = array_merge(
                    $composer['require'] ?? [],
                    $composer['require-dev'] ?? []
                );
                foreach (array_keys($allPackages) as $pkg) {
                    if (str_starts_with($pkg, 'moox/')) {
                        $names[$pkg] = true;
                    }
                }
            }
        }

        return array_keys($names);
    }

    public static function providerClassForPackage(string $packageName, ?string $basePath = null): ?string
    {
        $basePath ??= base_path();
        $packageParts = explode('/', $packageName);
        $packageDir = $packageParts[1] ?? null;

        if ($packageDir === null) {
            return null;
        }

        $possiblePaths = [
            $basePath.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.$packageDir.DIRECTORY_SEPARATOR.'composer.json',
            $basePath.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.$packageName.DIRECTORY_SEPARATOR.'composer.json',
        ];

        foreach ($possiblePaths as $composerPath) {
            if (! File::exists($composerPath)) {
                continue;
            }

            $composer = json_decode(File::get($composerPath), true);
            $providerClasses = $composer['extra']['laravel']['providers'] ?? [];

            if (! empty($providerClasses)) {
                return $providerClasses[0];
            }
        }

        return null;
    }

    public static function isMooxProvider(string $providerClass): bool
    {
        if (! class_exists($providerClass)) {
            return false;
        }

        try {
            $reflection = new \ReflectionClass($providerClass);

            return $reflection->isSubclassOf(MooxServiceProvider::class);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @return array<string, string> package name => provider class
     */
    public static function scanMooxProviders(?string $basePath = null): array
    {
        $basePath ??= base_path();
        $result = [];

        foreach (self::mooxPackageNames($basePath) as $packageName) {
            $providerClass = self::providerClassForPackage($packageName, $basePath);

            if (! $providerClass || ! self::isMooxProvider($providerClass)) {
                continue;
            }

            $result[$packageName] = $providerClass;
        }

        return $result;
    }

    public static function composerPathForPackage(string $packageName, ?string $basePath = null): ?string
    {
        $basePath ??= base_path();
        $packageParts = explode('/', $packageName);
        $packageDir = $packageParts[1] ?? null;

        if ($packageDir === null) {
            return null;
        }

        $possiblePaths = [
            $basePath.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.$packageDir.DIRECTORY_SEPARATOR.'composer.json',
            $basePath.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.$packageName.DIRECTORY_SEPARATOR.'composer.json',
        ];

        foreach ($possiblePaths as $composerPath) {
            if (File::exists($composerPath)) {
                return $composerPath;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function readComposerJson(string $packageName, ?string $basePath = null): ?array
    {
        $composerPath = self::composerPathForPackage($packageName, $basePath);

        if ($composerPath === null) {
            return null;
        }

        $composer = json_decode(File::get($composerPath), true);

        return is_array($composer) ? $composer : null;
    }

    public static function packageSlug(string $packageName): string
    {
        $parts = explode('/', $packageName);

        return $parts[1] ?? $packageName;
    }

    public static function packageNamespace(string $packageName): string
    {
        $slug = self::packageSlug($packageName);

        return 'Moox\\'.str_replace(' ', '', ucwords(str_replace('-', ' ', $slug)));
    }

    public static function resolveSeederClass(string $packageName, string $seeder): ?string
    {
        if (class_exists($seeder)) {
            return $seeder;
        }

        $packageNamespace = self::packageNamespace($packageName);

        $possibleClasses = [
            $packageNamespace.'\\Database\\Seeders\\'.ucfirst($seeder),
            $packageNamespace.'\\Database\\Seeders\\'.$seeder,
            $packageNamespace.'\\Seeders\\'.ucfirst($seeder),
            $packageNamespace.'\\Seeders\\'.$seeder,
        ];

        $composer = self::readComposerJson($packageName);
        $seedPath = $composer['extra']['moox']['install']['seed'] ?? null;
        if (is_string($seedPath) && $seedPath !== '') {
            $basename = basename($seedPath, '.php');
            $possibleClasses[] = $packageNamespace.'\\Database\\Seeders\\'.ucfirst($basename);
            $possibleClasses[] = $packageNamespace.'\\Database\\Seeders\\'.$basename;
        }

        foreach ($possibleClasses as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * @return array<string, list<string>> slug => list of moox/* dependency slugs
     */
    public static function mooxDependencyGraph(?string $basePath = null): array
    {
        $basePath ??= base_path();
        $graph = [];

        foreach (self::mooxPackageNames($basePath) as $packageName) {
            $slug = self::packageSlug($packageName);
            $composer = self::readComposerJson($packageName, $basePath);
            $deps = [];

            if (is_array($composer)) {
                foreach (['require', 'require-dev'] as $section) {
                    foreach ($composer[$section] ?? [] as $name => $_constraint) {
                        if (is_string($name) && str_starts_with($name, 'moox/')) {
                            $deps[] = self::packageSlug($name);
                        }
                    }
                }
            }

            $graph[$slug] = array_values(array_unique($deps));
        }

        return $graph;
    }
}
