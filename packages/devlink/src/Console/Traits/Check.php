<?php

namespace Moox\Devlink\Console\Traits;

trait Check
{
    private function check(): array
    {
        $status = 'unknown';
        $message = 'Devlink is in unknown status, run `php artisan devlink:link` to update';
        $hasDevlink = file_exists(base_path('composer.json-devlink'));
        $hasDeploy = file_exists(base_path('composer.json-deploy'));

        if ($hasDevlink && ! $hasDeploy) {
            $status = 'linked';
            $message = 'Devlink is linked, happy coding!';
        } elseif (! $hasDevlink && $hasDeploy) {
            $status = 'deploy';
            $message = 'Devlink is unlinked, ready for deployment!';
        }

        $packagesArray = [];
        $realPackages = [];

        $lasterror = null;

        $config = config('devlink');

        if (! isset($config['packages'])) {
            $lasterror = 'No packages configured in config/devlink.php';
        }

        $composerJson = json_decode(file_get_contents($this->composerJsonPath), true);
        $repositories = $composerJson['repositories'] ?? [];

        foreach ($config['packages'] as $name => $package) {
            $packagePath = "packages/{$name}";
            $isLocal = ($package['type'] ?? '') === 'local';
            $isPrivate = ($package['type'] ?? '') === 'private';

            $isLinked = false;
            if ($isLocal) {
                foreach ($repositories as $repo) {
                    if (($repo['type'] ?? '') === 'path' && ($repo['url'] ?? '') === $packagePath) {
                        $isLinked = true;
                        break;
                    }
                }
            } else {
                $isLinked = is_link($packagePath);
            }

            $packagesArray[$name] = $package;

            $realPackages[$name] = [
                'name' => $name,
                'type' => $package['type'] ?? 'unknown',
                'active' => $package['active'] ?? false,
                'link' => $package['linked'] ?? true,
                'deploy' => $package['deploy'] ?? false,
                'valid' => match (true) {
                    $isLocal => is_dir($packagePath),
                    $isPrivate => is_dir($package['path'] ?? ''),
                    default => is_dir($package['path'] ?? ''),
                },
                'linked' => $isLinked,
                'path' => $package['path'] ?? null,
                'config' => $package,
            ];

            if (! $isPrivate && isset($package['path']) && str_contains($package['path'], 'disabled')) {
                unset($realPackages[$name]);
            }
        }

        $packagesPath = $config['packages_path'] ?? 'packages';

        $publicBasePath = $config['public_base_path'] ?? '../moox';

        $privateBasePath = $config['private_base_path'] ?? 'disabled';

        if (! is_dir($packagesPath)) {
            $lasterror = 'Packages path is invalid';
        }

        if (! is_dir($publicBasePath)) {
            $lasterror = 'Public base path - '.$publicBasePath.' - is invalid';
        }

        if (! is_dir($privateBasePath) && $privateBasePath !== 'disabled') {
            $lasterror = 'Private base path - '.$privateBasePath.' - is invalid';
        }

        if (! file_exists(base_path('composer.json'))) {
            $lasterror = 'composer.json does not exist';
        }

        if (file_exists(base_path('composer.json-linked'))) {
            $status = 'linked';
            $message = 'Devlink is linked';
        }

        if (file_exists(base_path('composer.json-deploy'))) {
            $status = 'deploy';
            $message = 'Devlink is unlinked and ready for deployment';
        }

        if ($lasterror !== null) {
            $status = 'error';
            $message = $lasterror;
        }

        $fullStatus = [
            'status' => $status,
            'message' => $message,
            'packages_path' => $packagesPath,
            'public_base_path' => $publicBasePath,
            'private_base_path' => $privateBasePath,
            'packages' => $realPackages,
            'updated' => $this->checkUpdated(),
        ];

        return $fullStatus;
    }

    private function checkUpdated(): bool
    {
        $composerJson = json_decode(file_get_contents($this->composerJsonPath), true);
        $devlinkConfig = config('devlink.packages');
        $composerRequire = array_merge(
            $composerJson['require'] ?? [],
            $composerJson['require-dev'] ?? []
        );

        foreach ($devlinkConfig as $package => $config) {
            if (! ($config['active'] ?? false)) {
                continue;
            }

            $packageName = $this->getPackageName($package, $config);
            if (! $packageName || ! isset($composerRequire[$packageName])) {
                return false;
            }
        }

        return true;
    }

    private function getInstalledVersion(string $name, array $package): ?string
    {
        $packageName = $this->getPackageName($name, $package);
        if (! $packageName) {
            return null;
        }

        $path = $package['path'] ?? '';
        if ($path && ! str_contains($path, 'disabled/')) {
            $composerJson = realpath(base_path($path)).'/composer.json';
            if (file_exists($composerJson)) {
                $composerData = json_decode(file_get_contents($composerJson), true);

                return $composerData['version'] ?? 'dev-main';
            }
        }

        $composerLock = base_path('composer.lock');
        if (! file_exists($composerLock)) {
            return null;
        }

        $lockData = json_decode(file_get_contents($composerLock), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            info("Invalid composer.lock JSON for $name");

            return null;
        }

        foreach ([$lockData['packages'] ?? [], $lockData['packages-dev'] ?? []] as $packages) {
            foreach ($packages as $pkg) {
                if (($pkg['name'] ?? '') === $packageName) {
                    return $pkg['version'] ?? null;
                }
            }
        }

        return null;
    }

    private function getShortPath(array $row): string
    {
        if (($row['type'] ?? '') === 'local') {
            return '-';
        }

        $privateBasePath = config('devlink.private_base_path');
        if (($row['type'] ?? '') === 'private' && $privateBasePath === 'disabled') {
            return '- enable private path in config -';
        }

        $path = $this->packages[$row['name']]['path'] ?? '';
        if (empty($path)) {
            return '-';
        }

        if (str_starts_with($path, '../')) {
            return $path;
        }

        $basePath = base_path();
        if (str_starts_with($path, $basePath)) {
            return substr($path, strlen($basePath) + 1);
        }

        return $path;
    }

    private function getPackageName(string $name, array $package): ?string
    {
        $isLocal = ($package['type'] ?? '') === 'local';
        $path = $isLocal ? "packages/$name" : ($package['path'] ?? '');

        if (! $path || str_contains($path, 'disabled/')) {
            return null;
        }

        if (str_starts_with($path, '../')) {
            $path = realpath(base_path($path));
        }

        $composerJson = "$path/composer.json";
        if (! file_exists($composerJson)) {
            return null;
        }

        $data = json_decode(file_get_contents($composerJson), true);

        return $data['name'] ?? null;
    }

    private function arePackagesInSync(array $packages): bool
    {
        $composerJson = base_path('composer.json');
        if (! file_exists($composerJson)) {
            return false;
        }

        $composerData = json_decode(file_get_contents($composerJson), true);
        if (! $composerData) {
            return false;
        }

        $composerRequire = array_merge(
            $composerData['require'] ?? [],
            $composerData['require-dev'] ?? []
        );

        foreach ($packages as $package) {
            if (! ($package['active'] ?? false) && ! ($package['linked'] ?? false)) {
                continue;
            }

            $packageName = $this->getPackageName($package['name'], $package['config']);
            if (! $packageName) {
                continue;
            }

            $expectedPath = $package['config']['path'] ?? '';
            if (empty($expectedPath)) {
                continue;
            }

            // If package is active but not linked, we're out of sync
            if (($package['active'] ?? false) && ! ($package['linked'] ?? false)) {
                return false;
            }

            // If package is linked but not in composer.json, we're out of sync
            if (! isset($composerRequire[$packageName])) {
                return false;
            }

            $composerPath = $composerRequire[$packageName];
            if (str_contains($composerPath, 'path:')) {
                $composerPath = trim(str_replace('path:', '', $composerPath));
                if ($composerPath !== $expectedPath) {
                    return false;
                }
            }
        }

        return true;
    }
}
