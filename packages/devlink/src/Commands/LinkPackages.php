<?php

namespace Moox\Devlink\Commands;

use Illuminate\Console\Command;

class LinkPackages extends Command
{
    protected $signature = 'devlink:link';

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
        $this->packagesPath = base_path('packages');
    }

    public function handle(): void
    {
        $this->art();
        $this->hello();
        $this->checks();
        $this->removeSymlinks();
        $this->createPackagesDirectory();
        $this->createSymlinks();
        $this->composerRemovePackages();
        $this->updateComposerJson();
        $this->runComposerUpdate();
        $this->artisanOptimizeClear();
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
        $this->info('Hello, I will link the packages for you.');
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

    private function createPackagesDirectory(): void
    {
        if (! is_dir($this->packagesPath)) {
            mkdir($this->packagesPath, 0755, true);
        }
    }

    private function removeSymlinks(): void
    {
        // Check for existing symlinks that are no longer in config
        $existingLinks = [];
        if (is_dir($this->packagesPath)) {
            foreach (scandir($this->packagesPath) as $item) {
                if ($item !== '.' && $item !== '..' && is_link("$this->packagesPath/$item")) {
                    if (! in_array($item, $this->packages)) {
                        $existingLinks[] = $item;
                    }
                }
            }
        }

        if ($existingLinks) {
            $this->warn("\nFound existing symlinks for packages no longer in config:");
            foreach ($existingLinks as $link) {
                $this->line("- $link → ".readlink("$this->packagesPath/$link"));
                if ($this->confirm("Remove symlink for $link?")) {
                    unlink("$this->packagesPath/$link");
                    $this->info("Removed symlink for $link");
                }
            }
            $this->line('');
        }
    }

    private function createSymlinks(): void
    {
        $linkedPackages = [];
        $notFoundPackages = [];
        $failedPackages = [];

        foreach ($this->packages as $package) {
            $found = false;

            foreach ($this->basePaths as $basePath) {
                $resolvedBasePath = $this->resolvePath($basePath);
                $target = "$resolvedBasePath/$package";
                $link = "$this->packagesPath/$package";

                if (is_dir($target)) {
                    $found = true;
                    try {
                        if (is_link($link) || is_dir($link)) {
                            unlink($link);
                        }
                        if (PHP_OS_FAMILY === 'Windows') {
                            exec('mklink /J '.escapeshellarg($link).' '.escapeshellarg($target));
                        } else {
                            symlink($target, $link);
                        }
                        $linkedPackages[] = "$package → $target";
                        break;
                    } catch (\Exception $e) {
                        $failedPackages[] = "$package ({$e->getMessage()})";
                    }
                }
            }

            if (! $found) {
                $notFoundPackages[] = $package;
            }
        }

        if ($linkedPackages) {
            $this->info('Successfully linked packages:');
            foreach ($linkedPackages as $package) {
                $this->line("✓ $package");
            }
        }

        if ($notFoundPackages) {
            $this->error('Packages not found in any base path:');
            foreach ($notFoundPackages as $package) {
                $this->line("✗ $package");
            }
            $this->line("\nSearched in paths:");
            foreach ($this->basePaths as $path) {
                $this->line('- '.$this->resolvePath($path));
            }
        }

        if ($failedPackages) {
            $this->error('Failed to link packages:');
            foreach ($failedPackages as $package) {
                $this->line("✗ $package");
            }
        }

        if (! $linkedPackages && ! $notFoundPackages && ! $failedPackages) {
            $this->info('No packages to link.');
        }
    }

    private function updateComposerJson(): void
    {
        if (! file_exists($this->composerJsonPath)) {
            $this->error('composer.json not found!');

            return;
        }

        $composerContent = file_get_contents($this->composerJsonPath);
        $composerJson = json_decode($composerContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid composer.json format: '.json_last_error_msg());

            return;
        }

        $repositories = $composerJson['repositories'] ?? [];
        $require = $composerJson['require'] ?? [];
        $updated = false;
        $addedRepos = [];
        $addedRequires = [];

        foreach ($this->packages as $package) {
            $packagePath = "packages/{$package}";
            $repoEntry = ['type' => 'path', 'url' => $packagePath, 'options' => ['symlink' => true]];
            $packageName = "moox/{$package}";

            if (! collect($repositories)->contains(fn ($repo) => $repo['url'] === $packagePath)) {
                $repositories[] = $repoEntry;
                $updated = true;
                $addedRepos[] = $package;
            }

            if (! isset($require[$packageName])) {
                $require[$packageName] = '*';
                $updated = true;
                $addedRequires[] = $packageName;
            }
        }

        if ($updated) {
            $composerJson['repositories'] = $repositories;
            $composerJson['require'] = $require;
            file_put_contents($this->composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            if ($addedRepos) {
                $this->info('Added repository entries for: '.implode(', ', $addedRepos));
            }
            if ($addedRequires) {
                $this->info('Added requirements for: '.implode(', ', $addedRequires));
            }
        } else {
            $this->info('No changes needed in composer.json');
        }
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

    private function composerRemovePackages(): void
    {
        $composerJson = json_decode(file_get_contents($this->composerJsonPath), true);
        $removedPackages = [];

        foreach ($composerJson['repositories'] ?? [] as $key => $repo) {
            if ($repo['type'] === 'path' && str_starts_with($repo['url'], 'packages/')) {
                $package = basename($repo['url']);
                if (! in_array($package, $this->packages)) {
                    $removedPackages[] = [
                        'key' => $key,
                        'package' => $package,
                        'name' => "moox/$package",
                    ];
                }
            }
        }

        if ($removedPackages) {
            $this->warn("\nFound composer.json entries for removed packages:");
            foreach ($removedPackages as $removed) {
                $this->line("- {$removed['package']} ({$removed['name']})");
            }

            if ($this->confirm('Remove these entries from composer.json?')) {
                foreach ($removedPackages as $removed) {
                    unset($composerJson['repositories'][$removed['key']]);
                    unset($composerJson['require'][$removed['name']]);
                }
                $composerJson['repositories'] = array_values($composerJson['repositories']);
                file_put_contents(
                    $this->composerJsonPath,
                    json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
                );
                $this->info('Removed composer.json entries for removed packages');
            }
        }
    }

    private function artisanOptimizeClear(): void
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
