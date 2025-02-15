<?php

namespace Moox\Devlink\Console\Traits;

trait Link
{
    private function link(): void
    {
        $this->removeSymlinks();
        $this->createSymlinks();
    }

    /**
     * Create symlinks for all configured packages.
     */
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

    /**
     * Remove existing symlinks that are no longer in config.
     */
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

    /**
     * Remove packages from composer.json.
     */
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

    private function updateComposerJson(): void
    {
        if (! file_exists($this->composerJsonPath)) {
            $this->error('composer.json not found!');

            return;
        }

        // Read original composer.json
        $composerContent = file_get_contents($this->composerJsonPath);
        $composerJson = json_decode($composerContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid composer.json format: '.json_last_error_msg());

            return;
        }

        // Backup original before modifications
        file_put_contents($this->composerJsonPath.'-original', $composerContent);

        // Update development composer.json (with all packages and repositories)
        $repositories = $composerJson['repositories'] ?? [];
        $require = $composerJson['require'] ?? [];
        $allPackages = array_merge($this->packages, config('devlink.internal_packages', []));

        foreach ($allPackages as $package) {
            $packagePath = "packages/{$package}";
            $repoEntry = ['type' => 'path', 'url' => $packagePath, 'options' => ['symlink' => true]];
            $packageName = "moox/{$package}";

            if (! collect($repositories)->contains(fn ($repo) => $repo['url'] === $packagePath)) {
                $repositories[] = $repoEntry;
            }
            if (! isset($require[$packageName])) {
                $require[$packageName] = '*';
            }
        }

        $composerJson['repositories'] = $repositories;
        $composerJson['require'] = $require;
        file_put_contents(
            $this->composerJsonPath,
            json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );

        // Create composer.json-deploy (only public packages, no repositories)
        $deployJson = $composerJson;
        unset($deployJson['repositories']);
        $deployJson['minimum-stability'] = 'stable';

        // Remove internal packages from require
        foreach (config('devlink.internal_packages', []) as $package) {
            unset($deployJson['require']["moox/{$package}"]);
        }

        file_put_contents(
            $this->composerJsonPath.'-deploy',
            json_encode($deployJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );

        $this->info('Updated composer.json and created composer.json-deploy');

        $devlinkStatus = 'linked';
    }
}
