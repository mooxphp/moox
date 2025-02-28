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

        foreach ($devlinkConfig as $package => $config) {
            if (! ($config['active'] ?? false)) {
                continue;
            }

            $packageName = 'moox/'.$package;
            if (! isset($composerJson['require'][$packageName]) && ! isset($composerJson['require-dev'][$packageName])) {
                return false;
            }
        }

        return true;
    }
}
