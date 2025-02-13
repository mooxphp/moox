<?php

namespace Moox\Devlink\Commands;

use Illuminate\Console\Command;

class DeployPackages extends Command
{
    protected $signature = 'devlink:deploy';

    protected $description = 'Symlink Moox packages into the project from multiple base paths and ensure composer.json is updated';

    protected array $basePaths;

    protected array $packages;

    protected string $composerJsonPath;

    protected string $packagesPath;

    public function __construct()
    {
        parent::__construct();

        $this->basePaths = config('devlink.base_paths', []);
        $this->packages = config('devlink.packages', []);

        if (empty($this->basePaths)) {
            $this->warn('No base paths configured in config/devlink.php');
        }

        if (empty($this->packages)) {
            $this->warn('No packages configured in config/devlink.php');
        }

        $this->composerJsonPath = base_path('composer.json');
        $this->packagesPath = config('devlink.packages_path', base_path('packages'));
    }

    public function handle(): void
    {
        $this->art();
        $this->hello();
        $this->checks();
        $this->removeAllSymlinks();
        $this->removePackagesDirectoryIfEmpty();
        $this->restoreComposerJson();
        $this->runComposerUpdate();
        $this->optimizeClear();
        $this->queueRestart();
        $this->goodbye();
    }

    public function art(): void
    {
        $this->info('

        ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓ ▓▓▓▓▓▓▓▓▓▓▓       ▓▓▓▓▓▓▓▓▓▓▓▓           ▓▓▓▓▓▓▓▓▓▓▓▓   ▓▓▓▓▓▓▓        ▓▓▓▓▓▓▓
        ▓▓▒░░▒▓▓▒▒░░░░░░▒▒▓▓▓▒░░░░░░░▒▓▓   ▓▓▓▓▒░░░░░░░▒▓▓▓▓     ▓▓▓▓▓▒░░░░░░░▒▒▓▓▓▓▓▒▒▒▒▓▓      ▓▓▓▒▒▒▒▓▓
        ▓▒░░░░░░░░░░░░░░░░░░░░░░░░░░░░░▓▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▓ ▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▓░░░░░▒▓▓   ▓▓▒░░░░░▓▓
        ▓▒░░░░░░▒▓▓▓▓▒░░░░░░░▒▓▓▓▓░░░░░▒▓▓▓░░░░░▒▓▓▓▓▒░░░░░░░▓▓▓▓░░░░░░▒▓▓▓▓▓░░░░░░▒▓▓░░░░░▒▓▓▓▓▓░░░░░▒▓▓
        ▓▒░░░░▓▓▓▓  ▓▓░░░░░▓▓▓  ▓▓▓░░░░▒▓▓░░░░▒▓▓▓   ▓▓▓▓░░░░░▓░░░░░░▓▓▓▓   ▓▓▓▒░░░░▓▓▓▒░░░░░▓▓▓░░░░░▓▓▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓░░░░▒▓▓        ▓▓▓░░▒░░░░░▓▓▓        ▓▓░░░░▒▓▓▓▓░░░░░░░░░░░▓▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓░░░░▒▓          ▓▓▓░░░░░▒▓▓          ▓▓▒░░░░▓ ▓▓▓░░░░░░░░░▓▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓░░░░▒▓▓        ▓▓▒░░░░░▒░░▒▓▓        ▓▓░░░░▒▓▓▓▒░░░░░▒░░░░░▒▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓▓░░░░▒▓▓▓   ▓▓▓▒░░░░░▒▒░░░░░▒▓▓▓   ▓▓▓░░░░░▓▓▓░░░░░▒▓▓▓░░░░░▒▓▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓▓▓░░░░░░▒▒▓▓▒░░░░░░▒▓▓▓▓░░░░░░░▒▒▓▓▒░░░░░░▓▓▓░░░░░▒▓▓▓▓▓▒░░░░░▓▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▓ ▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▒░░░░░▓▓▓   ▓▓▒░░░░░▒▓
        ▓▓░░░▒▓▓    ▓▓▒░░░▒▓▓    ▓▓░░░░▓▓  ▓▓▓▓▒░░░░░░▒▒▓▓▓▓     ▓▓▓▓▓▒▒░░░░░▒▒▓▓▓▓▓░░░░▒▓▓      ▓▓▓░░░░▒▓
        ▓▓▓▓▓▓▓      ▓▓▓▓▓▓▓     ▓▓▓▓▓▓▓▓    ▓▓▓▓▓▓▓▓▓▓▓▓           ▓▓▓▓▓▓▓▓▓▓▓▓  ▓▓▓▓▓▓▓▓        ▓▓▓▓▓▓▓▓

        ');
    }

    private function hello(): void
    {
        $this->info('Hello, I will prepare your project for deployment.');
    }

    private function checks(): void
    {
        $this->line("\nConfiguration:");
        $this->line('Base paths:');
        if (empty($this->basePaths)) {
            $this->warn('- No base paths configured');
        } else {
            foreach ($this->basePaths as $path) {
                $resolvedPath = $this->resolvePath($path);
                $this->line("- $path");
                $this->line("  → $resolvedPath".(is_dir($resolvedPath) ? ' (exists)' : ' (not found)'));
            }
        }

        $this->line("\nConfigured packages:");
        if (empty($this->packages)) {
            $this->warn('No packages configured in config/devlink.php');
        } else {
            foreach ($this->packages as $package) {
                $this->line("- $package");
            }
        }

        $this->line('');
    }

    private function removeAllSymlinks(): void
    {
        if (is_dir($this->packagesPath)) {
            foreach (scandir($this->packagesPath) as $item) {
                if ($item !== '.' && $item !== '..' && is_link("$this->packagesPath/$item")) {
                    unlink("$this->packagesPath/$item");
                }
            }
        }
    }

    private function removePackagesDirectoryIfEmpty(): void
    {
        if (is_dir($this->packagesPath) && count(scandir($this->packagesPath)) === 2) {
            rmdir($this->packagesPath);
        }
    }

    private function restoreComposerJson(): void
    {
        $deployFile = $this->composerJsonPath.'-deploy';
        if (! file_exists($deployFile)) {
            $this->error('composer.json-deploy not found!');

            return;
        }

        unlink($this->composerJsonPath);
        rename($deployFile, $this->composerJsonPath);
        $this->info('Restored composer.json from composer.json-deploy');
    }

    private function runComposerUpdate(): void
    {
        if ($this->confirm('Run composer update now?', true)) {
            $output = [];
            $returnVar = 0;
            exec('composer update 2>&1', $output, $returnVar);

            if ($returnVar !== 0) {
                $this->error('Composer update failed: '.implode("\n", $output));

                return;
            }

            $this->info('Composer update completed successfully');
        } else {
            $this->info("Please run 'composer update' manually");
        }
    }

    private function optimizeClear(): void
    {
        if ($this->confirm('Run artisan optimize:clear now?', true)) {
            $this->info('Clearing cache...');
            $this->call('optimize:clear');
            $this->info('Cache cleared successfully');
        } else {
            $this->info("Please run 'artisan optimize:clear' manually");
        }
    }

    private function queueRestart(): void
    {
        if ($this->confirm('Run queue:restart now?', false)) {
            $this->info('Restarting queue...');
            $this->call('queue:restart');
        }
    }

    private function goodbye(): void
    {
        $this->info('Have a nice dev!');
    }

    private function resolvePath(string $path): string
    {
        return str_starts_with($path, '~/') ? str_replace('~', getenv('HOME'), $path) : rtrim(realpath($path) ?: $path, '/');
    }
}
