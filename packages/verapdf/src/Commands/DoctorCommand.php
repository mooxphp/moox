<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Moox\VeraPdf\Services\VeraPdfService;
use Moox\VeraPdf\Support\VeraPdfOutputPath;
use RuntimeException;

class DoctorCommand extends Command
{
    protected $signature = 'verapdf:doctor';

    protected $description = 'Check veraPDF installation health';

    public function handle(VeraPdfService $veraPdf): int
    {
        $allGood = true;

        if ($veraPdf->javaAvailable()) {
            $this->components->info('Java: OK');
        } else {
            $this->components->error('Java: NOT FOUND');
            $allGood = false;
        }

        try {
            $launcher = $veraPdf->launcherPath();
            $this->components->info("Launcher: {$launcher}");
        } catch (RuntimeException $e) {
            $this->components->error('Launcher: '.$e->getMessage());
            $allGood = false;
        }

        if ($veraPdf->isInstalled()) {
            $this->components->info('Installed: yes');
        } else {
            $this->components->error('Installed: no');
            $allGood = false;
        }

        if ($veraPdf->hasCliBinaries()) {
            $this->components->info('CLI binaries: OK');
        } else {
            $this->components->error('CLI binaries: NOT FOUND (expected bin/*cli*.jar from the veraPDF CLI pack)');
            $allGood = false;
        }

        if ($veraPdf->hasGuiArtefacts()) {
            $this->components->warn('GUI pack artefacts present; slim/headless installs use the CLI pack only.');
        }

        $outputPath = VeraPdfOutputPath::resolve();
        try {
            File::ensureDirectoryExists($outputPath);
            $this->components->info("Report output: {$outputPath}");
        } catch (\Throwable) {
            $this->components->warn("Report output: {$outputPath} (not writable)");
        }

        $this->newLine();

        if ($allGood) {
            $this->components->info('Everything looks good.');

            return self::SUCCESS;
        }

        $this->components->warn('Issues found. Run php artisan verapdf:install to fix.');

        return self::FAILURE;
    }
}
