<?php

declare(strict_types=1);

namespace Moox\KositValidator\Commands;

use Illuminate\Console\Command;
use Moox\KositValidator\Services\KositInstaller;
use Moox\KositValidator\Services\KositService;
use Moox\KositValidator\Support\InstallerBasePathGuard;
use Moox\KositValidator\Support\KositInstallPaths;
use RuntimeException;

class InstallKositCommand extends Command
{
    protected $signature = 'kosit:install
        {--force : Overwrite existing installation}';

    protected $description = 'Download and install KoSIT Validator + XRechnung configuration';

    public function handle(KositService $kosit, KositInstaller $installer): int
    {
        $this->components->info('Checking Java ...');

        if (! $kosit->javaAvailable()) {
            $this->components->error(
                'Java not found. Install a JRE/JDK on the server first (e.g. sudo apt install default-jre-headless).'
            );

            return self::FAILURE;
        }

        $this->components->info('Java found.');

        $basePath = (string) config('kosit-validator.base_path');

        try {
            InstallerBasePathGuard::assertValid($basePath);
            $paths = KositInstallPaths::fromBasePath($basePath);
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if ($kosit->isInstalled() && ! $this->option('force')) {
            $this->components->info('KoSIT is already installed. Use --force to reinstall.');

            return self::SUCCESS;
        }

        try {
            $installer->install(
                $paths,
                (bool) $this->option('force'),
                fn (string $message) => $this->components->info($message),
                fn (string $dirBasename, string $backupBasename) => $this->components->warn(
                    "Backing up {$dirBasename} to {$backupBasename}"
                ),
            );
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        try {
            $jarPath = $kosit->jarPath();
            $scenariosPath = $kosit->scenariosPath();
        } catch (RuntimeException $e) {
            $this->components->error('Installation incomplete: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('KoSIT installation successful.');
        $this->line("  JAR:          <info>{$jarPath}</info>");
        $this->line("  scenarios.xml: <info>{$scenariosPath}</info>");
        $this->newLine();
        $this->line('Test with: <comment>php artisan kosit:validate /path/to/invoice.xml</comment>');

        return self::SUCCESS;
    }
}
