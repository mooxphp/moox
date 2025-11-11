<?php

namespace Moox\Core\Console\Traits;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

trait CheckForMooxPackages
{
    public function checkForMooxPackage(string $package): bool
    {
        $composerJsonPath = base_path('composer.json');
        $composerJson = json_decode(file_get_contents($composerJsonPath), true);

        $packages = array_merge(
            $composerJson['require'] ?? [],
            $composerJson['require-dev'] ?? []
        );

        if (array_key_exists($package, $packages)) {
            info("\n✅ Package '{$package}' is already installed. Skipping installation.\n");

            return true;
        }

        error("❌ {$package} is not installed. Please run: composer require {$package}");

        if (! confirm("📦 Do you want to install {$package} now?", true)) {
            info('⛔ Installation cancelled.');

            return false;
        }

        $package = (string) $package;

        info("📦 Running: composer require {$package}:* ...");
        exec("composer require {$package}:* 2>&1", $output, $returnVar);
        foreach ($output as $line) {
            info('    '.$line);
        }

        if ($returnVar !== 0) {
            error("❌ Composer installation of {$package} failed. Please check your setup.");

            return false;
        }

        info("✅ {$package} successfully installed.");

        return true;
    }

    public function getInstalledMooxPackages(): array
    {
        $composerJsonPath = base_path('composer.json');
        $composerJson = json_decode(file_get_contents($composerJsonPath), true);

        $packages = array_merge(
            $composerJson['require'] ?? [],
            $composerJson['require-dev'] ?? []
        );

        return collect(array_keys($packages))
            ->filter(fn ($pkg) => str_starts_with($pkg, 'moox/'))
            ->values()
            ->toArray();
    }
}
