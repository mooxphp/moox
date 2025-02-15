<?php

namespace Moox\Devlink\Console\Traits;

trait Check
{
    private function check(): array
    {
        $status = 'unknown';
        $message = 'Devlink is in unknown status';

        $composerOriginal = false;
        $composerDeploy = false;

        $packagesArray = [];
        $realPackages = [];

        $lasterror = null;

        $config = config('devlink');

        if (! isset($config['packages'])) {
            $lasterror = 'No packages configured in config/devlink.php';
        }

        foreach ($config['packages'] as $package => $packageConfig) {
            $packagesArray[$package] = $packageConfig;
        }

        foreach ($packagesArray as $package => $packageConfig) {
            if ($packageConfig['active']) {
                $active = true;
            } else {
                $active = false;
            }

            if ($packageConfig['linked']) {
                $link = true;
            } else {
                $link = false;
            }

            if ($packageConfig['deploy']) {
                $deploy = true;
            } else {
                $deploy = false;
            }

            $fullPath = base_path($packageConfig['path']);
            $cleanPath = rtrim($this->resolvePath(dirname($fullPath)), '/').'/'.basename($fullPath);

            if (! is_dir($cleanPath)) {
                $valid = false;
            } else {
                $valid = true;
            }

            $composer = json_decode(file_get_contents(base_path('composer.json')), true);

            if (isset($composer['repositories'][$packageConfig['path']])) {
                $linked = true;
            } else {
                $linked = false;
            }

            $realPackages[$package] = [
                'name' => $package,
                'type' => $packageConfig['type'],
                'active' => $active,
                'link' => $link,
                'deploy' => $deploy,
                'valid' => $valid,
                'linked' => $linked,
            ];

            if (str_contains($packageConfig['path'], 'disabled')) {
                unset($realPackages[$package]);
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

        if (! file_exists(base_path('composer.json'))) {
            $lasterror = 'composer.json does not exist';
        }

        if (file_exists(base_path('composer.json-original'))) {
            $composerOriginal = true;
        }

        if (file_exists(base_path('composer.json-deploy'))) {
            $composerDeploy = true;
        }

        if (file_exists(base_path('composer.json-backup'))) {
            $composerBackup = true;
        }

        if (! $composerOriginal && ! $composerDeploy) {
            $status = 'unused';
            $message = 'Devlink is not active';
        }

        if (! $composerOriginal && $composerDeploy) {
            $status = 'unlinked';
            $message = 'Devlink is unlinked, not ready for deployment';
        }

        if ($composerOriginal && $composerDeploy) {
            $status = 'linked';
            $message = 'Devlink is linked, notready for deployment';
        }

        if ($composerOriginal && ! $composerDeploy) {
            $status = 'deployed';
            $message = 'Devlink is ready for deployment';
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
        ];

        return $fullStatus;
    }

    private function resolvePath(string $path): string
    {
        return str_starts_with($path, '~/') ? str_replace('~', getenv('HOME'), $path) : rtrim(realpath($path) ?: $path, '/');
    }
}
