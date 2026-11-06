<?php

namespace Moox\Core\Installer\Installers;

use Illuminate\Support\Facades\File;
use Moox\Core\Installer\AbstractAssetInstaller;
use Symfony\Component\Console\Input\StringInput;

use function Moox\Prompts\confirm;
use function Moox\Prompts\info;
use function Moox\Prompts\note;
use function Moox\Prompts\warning;

/**
 * Installer for database seeders.
 */
class SeederInstaller extends AbstractAssetInstaller
{
    public function getType(): string
    {
        return 'seeders';
    }

    public function getLabel(): string
    {
        return 'Seeders';
    }

    protected function getMooxInfoKey(): string
    {
        return 'seeders';
    }

    protected function getDefaultConfig(): array
    {
        return array_merge(parent::getDefaultConfig(), [
            'priority' => 50,
            'require_confirmation' => true,
        ]);
    }

    public function checkExists(string $packageName, array $items): bool
    {
        // Seeders don't really "exist" in the same way as other assets
        // They can be run multiple times, so we return false
        return false;
    }

    public function install(array $assets): bool
    {
        try {
            $selectedAssets = $this->selectItems($assets);
        } catch (\Exception $e) {
            warning("⚠️ Could not select seeders: {$e->getMessage()}");

            return true;
        }

        if (empty($selectedAssets)) {
            note('⏩ No seeders selected, skipping');

            return true;
        }

        // Confirmation prompt if configured
        try {
            if ($this->config['require_confirmation'] ?? true) {
                if (! confirm(label: 'Run seeders for all packages?', default: false)) {
                    note('⏩ Skipped seeders');

                    return true;
                }
            }
        } catch (\Exception $e) {
            warning("⚠️ Could not confirm seeder execution: {$e->getMessage()}");

            return true;
        }

        info("📦 Running {$this->getLabel()}...");

        $executed = 0;
        $failed = 0;
        $skipped = 0;
        $notFound = 0;

        foreach ($selectedAssets as $asset) {
            $packageName = $asset['package'];
            foreach ($asset['data'] as $seeder) {
                try {
                    $seederClass = $this->resolveSeederClass($packageName, $seeder);
                    if ($seederClass && class_exists($seederClass)) {
                        // Verwende $this->command->call() wenn verfügbar (nach Prompts funktioniert das besser)
                        // Das nutzt den korrekten IO-Context vom Command
                        if ($this->command) {
                            $this->command->call('db:seed', [
                                '--class' => $seederClass,
                                '--force' => true,
                            ]);
                        } else {
                            // Fallback: Direkt über Application mit sauberem IO-Context
                            $commandString = 'db:seed --class='.escapeshellarg($seederClass).' --force';
                            $input = new StringInput($commandString);
                            // Wichtig: Als interaktiv markieren, damit Prompts funktionieren
                            $input->setInteractive(true);
                            app()->handleCommand($input);
                        }
                        note("  ✅ {$seeder}: Executed");
                        $executed++;
                    } else {
                        note("  ℹ️ {$seeder}: Seeder class not found, skipping");
                        $notFound++;
                    }
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'already') || str_contains($e->getMessage(), 'duplicate')) {
                        note("  ℹ️ {$seeder}: Already seeded, skipping");
                        $skipped++;
                    } else {
                        warning("  ⚠️ {$seeder}: {$e->getMessage()}");
                        $failed++;
                    }
                }
            }
        }

        // Show summary
        if ($executed > 0) {
            info("✅ Executed {$executed} seeder(s)");
        }
        if ($skipped > 0) {
            note("ℹ️ Skipped {$skipped} seeder(s) (already executed)");
        }
        if ($notFound > 0) {
            note("ℹ️ Not found {$notFound} seeder(s)");
        }
        if ($failed > 0) {
            warning("⚠️ {$failed} seeder(s) failed");
        }

        return true; // Always return true to continue with other installers
    }

    protected function resolveSeederClass(string $packageName, string $seeder): ?string
    {
        // If seeder is already a full class name, return it
        if (class_exists($seeder)) {
            return $seeder;
        }

        // Try to resolve from package namespace
        $packageParts = explode('/', $packageName);
        $packageNamespace = 'Moox\\'.ucfirst($packageParts[1] ?? '');

        $possibleClasses = [
            $packageNamespace.'\\Database\\Seeders\\'.ucfirst($seeder),
            $packageNamespace.'\\Database\\Seeders\\'.$seeder,
            $packageNamespace.'\\Seeders\\'.ucfirst($seeder),
            $packageNamespace.'\\Seeders\\'.$seeder,
        ];

        foreach ($possibleClasses as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        // Hyphenated packages (e.g. moox/backup-server → Moox\BackupServerUi) need the file namespace
        return $this->resolveSeederClassFromFile($packageName, $seeder);
    }

    protected function resolveSeederClassFromFile(string $packageName, string $seeder): ?string
    {
        $packagePath = $this->getPackagePath($packageName);
        if ($packagePath === null) {
            return null;
        }

        $seederFile = $packagePath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'seeders'.DIRECTORY_SEPARATOR.$seeder.'.php';
        if (! File::exists($seederFile)) {
            return null;
        }

        $content = File::get($seederFile);
        if (
            ! preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)
            || ! preg_match('/class\s+(\w+)/', $content, $classMatch)
        ) {
            return null;
        }

        $class = $namespaceMatch[1].'\\'.$classMatch[1];

        return class_exists($class) ? $class : null;
    }
}
