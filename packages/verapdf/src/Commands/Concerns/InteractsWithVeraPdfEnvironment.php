<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Commands\Concerns;

use Moox\VeraPdf\DTOs\VeraPdfHealth;
use Moox\VeraPdf\Services\VeraPdfService;

trait InteractsWithVeraPdfEnvironment
{
    protected function requireJavaAvailable(VeraPdfService $veraPdf): ?int
    {
        if ($veraPdf->javaAvailable()) {
            return null;
        }

        $this->components->error($veraPdf->javaMissingMessage());

        return self::FAILURE;
    }

    protected function requireVeraPdfInstalled(VeraPdfService $veraPdf): ?int
    {
        if ($veraPdf->isInstalled()) {
            return null;
        }

        $this->components->error($veraPdf->notInstalledMessage());

        return self::FAILURE;
    }

    protected function renderVeraPdfHealth(VeraPdfHealth $health): int
    {
        if ($health->javaAvailable) {
            $this->components->info('Java: OK');
        } else {
            $this->components->error('Java: NOT FOUND');
        }

        if ($health->launcherPath !== null) {
            $this->components->info("Launcher: {$health->launcherPath}");
        } else {
            $this->components->error('Launcher: '.($health->launcherError ?? 'unknown error'));
        }

        if ($health->installed) {
            $this->components->info('Installed: yes');
        } else {
            $this->components->error('Installed: no');
        }

        if ($health->cliBinariesPresent) {
            $this->components->info('CLI binaries: OK');
        } else {
            $this->components->error('CLI binaries: NOT FOUND (expected bin/*cli*.jar from the veraPDF CLI pack)');
        }

        if ($health->guiArtefactsPresent) {
            $this->components->warn('GUI pack artefacts present; slim/headless installs use the CLI pack only.');
        }

        if ($health->outputPathWritable) {
            $this->components->info("Report output: {$health->outputPath}");
        } else {
            $this->components->warn("Report output: {$health->outputPath} (not writable)");
        }

        $this->newLine();

        if ($health->isHealthy()) {
            $this->components->info('Everything looks good.');

            return self::SUCCESS;
        }

        $this->components->warn('Issues found. Run php artisan verapdf:install to fix.');

        return self::FAILURE;
    }
}
