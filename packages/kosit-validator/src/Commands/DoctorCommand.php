<?php

declare(strict_types=1);

namespace Moox\KositValidator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Moox\KositValidator\Services\KositService;
use Moox\KositValidator\Support\KositOutputPath;
use RuntimeException;

class DoctorCommand extends Command
{
    protected $signature = 'kosit:doctor';

    protected $description = 'Check KoSIT Validator installation health';

    public function handle(KositService $kosit): int
    {
        $allGood = true;

        // Java
        if ($kosit->javaAvailable()) {
            $this->components->info('Java: OK');
        } else {
            $this->components->error('Java: NOT FOUND');
            $allGood = false;
        }

        // JAR
        try {
            $jar = $kosit->jarPath();
            $this->components->info("JAR: {$jar}");
        } catch (RuntimeException $e) {
            $this->components->error('JAR: '.$e->getMessage());
            $allGood = false;
        }

        // scenarios.xml
        try {
            $scenarios = $kosit->scenariosPath();
            $this->components->info("scenarios.xml: {$scenarios}");
        } catch (RuntimeException $e) {
            $this->components->error('scenarios.xml: '.$e->getMessage());
            $allGood = false;
        }

        $outputPath = KositOutputPath::resolve();
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

        $this->components->warn('Issues found. Run php artisan kosit:install to fix.');

        return self::FAILURE;
    }
}
