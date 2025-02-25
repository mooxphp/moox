<?php

namespace Moox\Devlink\Console\Traits;

use function Laravel\Prompts\info;
use function Laravel\Prompts\note;

trait Link
{
    private function link(): void
    {
        $this->prepare();
        $this->removeSymlinks();
        $this->composerRemovePackages();
        $this->createSymlinks();
        $this->updateComposerJson();
        $this->createDeployComposerJson();
    }

    private function prepare(): void
    {
        if (! is_dir($this->packagesPath)) {
            mkdir($this->packagesPath, 0755, true);
        }
    }

    private function createSymlinks(): void
    {
        $linkedPackages = [];
        $notFoundPackages = [];
        $failedPackages = [];
        $inactivePackages = [];
        $configuredPackages = config('devlink.packages', []);

        foreach ($configuredPackages as $name => $package) {
            if (! ($package['active'] ?? false)) {
                $inactivePackages[] = $name;

                continue;
            }

            $target = realpath($package['path']);
            if (! $target) {
                $target = $package['path'];
            }

            $link = "{$this->packagesPath}/$name";

            if (is_dir($target)) {
                try {
                    if (is_link($link) || is_dir($link)) {
                        if (is_link($link)) {
                            unlink($link);
                        } else {
                            rmdir($link);
                        }
                    }

                    info("Creating symlink for $link → $target");

                    if (PHP_OS_FAMILY === 'Windows') {
                        exec('mklink /J '.escapeshellarg($link).' '.escapeshellarg($target));
                    } else {
                        if (! symlink($target, $link)) {
                            throw new \RuntimeException('Failed to create symlink');
                        }
                    }
                    $linkedPackages[] = "$name → $target";
                } catch (\Exception $e) {
                    $failedPackages[] = "$name ({$e->getMessage()})";
                }
            } else {
                $notFoundPackages[] = "$name (path: $target)";
            }
        }

        if ($linkedPackages) {
            info('Successfully linked packages:');
            foreach ($linkedPackages as $package) {
                note("✓ $package");
            }
        }

        if ($notFoundPackages) {
            $this->error('Packages not found:');
            foreach ($notFoundPackages as $package) {
                note("✗ $package");
            }
        }

        if ($failedPackages) {
            $this->error('Failed to link packages:');
            foreach ($failedPackages as $package) {
                note("✗ $package");
            }
        }

        if (! $linkedPackages && ! $notFoundPackages && ! $failedPackages) {
            info('No packages to link.');
        }
    }

    private function removeSymlinks(): void
    {
        $configuredPackages = array_keys(config('devlink.packages', []));
        $existingLinks = [];

        if (is_dir($this->packagesPath)) {
            foreach (scandir($this->packagesPath) as $item) {
                if ($item !== '.' && $item !== '..' && is_link("$this->packagesPath/$item")) {
                    if (! in_array($item, $configuredPackages)) {
                        $existingLinks[] = $item;
                    }
                }
            }
        }

        if ($existingLinks) {
            info("\nFound existing symlinks for packages no longer in config:");
            foreach ($existingLinks as $link) {
                note("- $link → ".readlink("$this->packagesPath/$link"));
                if ($this->confirm("Remove symlink for $link?")) {
                    unlink("$this->packagesPath/$link");
                    info("Removed symlink for $link");
                }
            }
            info('');
        }
    }

    private function composerRemovePackages(): void
    {
        $composerJson = json_decode(file_get_contents($this->composerJsonPath), true);
        $removedPackages = [];
        $configuredPackages = config('devlink.packages', []);
        $packagesBaseName = basename($this->packagesPath);

        foreach ($composerJson['repositories'] ?? [] as $key => $repo) {
            if ($repo['type'] === 'path' && str_starts_with($repo['url'], $packagesBaseName.'/')) {
                $package = basename($repo['url']);

                if (! isset($configuredPackages[$package]) || ! ($configuredPackages[$package]['active'] ?? false)) {
                    $removedPackages[] = [
                        'key' => $key,
                        'package' => $package,
                        'name' => "moox/$package",
                    ];
                }
            }
        }

        if ($removedPackages) {
            info("\nFound composer.json entries for removed packages:");
            foreach ($removedPackages as $removed) {
                note("- {$removed['package']} ({$removed['name']})");
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
                info('Removed composer.json entries for removed packages');
            }
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

        info("\nChecking packages for composer.json updates:");

        foreach (config('devlink.packages', []) as $name => $package) {
            if (! ($package['active'] ?? false)) {
                continue;
            }

            $packageName = "moox/{$name}";
            $isPrivate = ($package['type'] ?? 'public') === 'private';

            if ($isPrivate) {
                $repoEntry = [
                    'type' => 'vcs',
                    'url' => $package['repo_url'] ?? config('devlink.private_repo_url'),
                ];
            } else {
                $packagePath = basename($this->packagesPath)."/{$name}";
                $repoEntry = [
                    'type' => 'path',
                    'url' => $packagePath,
                    'options' => [
                        'symlink' => true,
                    ],
                ];
            }

            $repoExists = false;
            foreach ($repositories as $repo) {
                if (($repo['type'] ?? '') === $repoEntry['type'] && ($repo['url'] ?? '') === $repoEntry['url']) {
                    $repoExists = true;
                    break;
                }
            }

            if (! $repoExists) {
                $repositories[] = $repoEntry;
                $addedRepos[] = $name.($isPrivate ? ' (private)' : '');
                $updated = true;
            }

            if (! isset($require[$packageName])) {
                $require[$packageName] = '*';
                $addedRequires[] = $packageName;
                $updated = true;
            }
        }

        if ($updated) {
            $composerJson['repositories'] = array_values($repositories);
            $composerJson['require'] = $require;
            file_put_contents(
                $this->composerJsonPath,
                json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
            );

            if ($addedRepos) {
                info('Added repository entries for: '.implode(', ', $addedRepos));
            }
            if ($addedRequires) {
                info('Added requirements for: '.implode(', ', $addedRequires));
            }
        } else {
            info('No changes needed in composer.json');
        }
    }

    private function createDeployComposerJson(): void
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

        $deployJson = $composerJson;
        unset($deployJson['repositories']);

        $deployPath = dirname($this->composerJsonPath).'/composer.json-deploy';
        file_put_contents(
            $deployPath,
            json_encode($deployJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );

        info('Created composer.json-deploy without repositories section');
    }
}
