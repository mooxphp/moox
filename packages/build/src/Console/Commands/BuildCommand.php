<?php

namespace Moox\Build\Console\Commands;

use Illuminate\Console\Command;
use Moox\Core\Console\Traits\ArtLeft;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class BuildCommand extends Command
{
    use ArtLeft;

    protected $signature = 'moox:build';

    protected $description = 'Build Laravel Packages and Filament Resources with a single command.';

    protected $emojiRocket = '🚀';

    protected $emojiStar = '⭐';

    protected $emojiSmile = '😊';

    protected $emojiCool = '😎';

    protected $emojiParty = '🎉';

    protected $emojiRainbow = '🌈';

    protected $emojiCheck = '✅';

    protected $emojiIdea = '💡';

    protected $emojiError = '❌';

    protected $emojiWarning = '⚠️';

    protected $emoji;

    protected $subject;

    protected $name;

    protected $type;

    protected $motivation;

    protected $sentence;

    protected $packageName;

    protected $entityName;

    protected $existingPackage;

    protected $authorName;

    protected $authorEmail;

    protected $namespace;

    protected $packagist;

    protected $packageDescription;

    public function handle()
    {
        $this->art();
        $this->info('  Welcome to the Moox Build Command!  '.$this->emojiRocket);
        $this->info(' ');
        $this->askForWhatToBuild();
        $this->askForPackageType();
        if ($this->type === 'Entity') {
            $this->askForExistingPackage();
        } else {
            $this->askForAuthorName();
            $this->askForAuthorEmail();
            $this->askForNamespace();
            $this->askForPackagist();
            $this->askForPackageName();
            $this->askForPackageDescription();
        }
        if ($this->subject === 'Package') {
            $this->buildPackage();
        } else {
            $this->askForEntityName();
            if ($this->existingPackage) {
                $this->buildEntity();
            } else {
                $this->buildPackageWithEntity();
            }
        }
        note('  '.$this->emojiRocket.'  Build completed successfully! '.$this->emojiRocket);
    }

    protected function askForWhatToBuild(): void
    {
        $choices = [
            'Package' => 'A new Package',
            'Entity' => 'An Entity for a Package',
        ];

        $this->type = select('What do you want to build right now?', $choices, scroll: 10);
    }

    protected function askForExistingPackage(): void
    {
        $packages = $this->findAvailableMooxPackages();

        if (empty($packages)) {
            $this->error('No Moox packages found. Please create a package first.');
            exit;
        }

        $this->existingPackage = select('For which package do you want to build an entity?', $packages, scroll: 10);
    }

    protected function findAvailableMooxPackages(): array
    {
        $packagesPath = base_path('packages');
        $packages = [];

        if (! is_dir($packagesPath)) {
            return $packages;
        }

        $directories = glob($packagesPath.'/*', GLOB_ONLYDIR);

        foreach ($directories as $directory) {
            $serviceProviderFiles = glob($directory.'/src/*ServiceProvider.php');

            foreach ($serviceProviderFiles as $file) {
                $content = file_get_contents($file);

                if (strpos($content, 'extends MooxServiceProvider') !== false ||
                    strpos($content, 'extends \Moox\Core\Providers\MooxServiceProvider') !== false) {
                    $packageName = basename($directory);

                    $displayName = $this->formatPackageNameForDisplay($packageName);

                    $packages[$packageName] = $displayName;
                }
            }
        }

        return $packages;
    }

    protected function formatPackageNameForDisplay(string $packageName): string
    {
        $parts = explode('-', $packageName);
        $formattedParts = array_map(function ($part) {
            return ucfirst($part);
        }, $parts);

        return 'Moox '.implode(' ', $formattedParts);
    }

    protected function askForPackageType(): void
    {
        $templates = config('build.package_templates');

        if ($this->type === 'Entity') {
            $templates = array_filter($templates, function ($template) {
                return $template['name'] !== 'Empty Package';
            });
        }

        $choices = collect($templates)->mapWithKeys(fn ($template, $key) => [
            $key => $template['select'],
        ])->toArray();

        $type = select('What Type of Entity do you want to build?', $choices, scroll: 10);

        $selectedTemplate = $templates[$type] ?? null;

        if (! $selectedTemplate) {
            throw new \RuntimeException('Selected template not found');
        }

        $this->subject = $selectedTemplate['subject'];
        $this->motivation = $selectedTemplate['motivation'];

        $this->emoji = $this->{$selectedTemplate['emoji']};

        switch ($this->emoji) {
            case 'emojiRainbow':
                $this->emoji = $this->emojiRainbow;
                break;
            case 'emojiCool':
                $this->emoji = $this->emojiCool;
                break;
            case 'emojiParty':
                $this->emoji = $this->emojiParty;
                break;
            case 'emojiRocket':
                $this->emoji = $this->emojiRocket;
                break;
            case 'emojiSmile':
                $this->emoji = $this->emojiSmile;
                break;
        }

        $this->sentence = $selectedTemplate['sentence'];

        $this->name = $selectedTemplate['name'];

        info('  '.$this->motivation.' '.$this->emoji);
        info('  Let\'s build a Moox '.$this->subject.' '.$this->sentence);
    }

    protected function askForAuthorName(): void
    {
        $this->authorName = text('What is your name?', default: config('build.default_author.name'));

        info('  Hello '.$this->authorName.'! Nice to meet you. '.$this->emojiSmile);
    }

    protected function askForAuthorEmail(): void
    {
        $this->authorEmail = text('What is your email?', default: config('build.default_author.email'));

        info('  Great! '.$this->authorEmail.', now I can spam you.');
    }

    protected function askForNamespace(): void
    {
        $this->namespace = text('What is the namespace of the package?', default: config('build.default_namespace'));
    }

    protected function askForPackagist(): void
    {
        $this->packagist = text('What is the packagist name of the package?', default: config('build.default_packagist'));
    }

    protected function askForPackageName(): void
    {
        $this->packageName = text('What is the name of the package?', placeholder: 'Awesome '.$this->subject);

        if (empty($this->packageName)) {
            error('  Please provide a valid package name.');
            $this->askForPackageName();
        }
    }

    protected function askForPackageDescription(): void
    {
        $this->packageDescription = $this->packageName.' is a Moox '.$this->subject.' '.$this->sentence;

        $this->packageDescription = text('What is the description of the package?', placeholder: $this->packageDescription);

        if (empty($this->packageDescription)) {
            error('  Please provide a valid package description.');
            $this->askForPackageDescription();
        }
    }

    protected function askForEntityName(): void
    {
        if ($this->existingPackage) {
            $this->entityName = text('What is the name of the '.$this->subject.'?');
        } else {
            $this->entityName = text('What is the name of the '.$this->subject.'?', default: $this->packageName);
        }

        if (empty($this->entityName)) {
            error('  Please provide a valid entity name.');
            $this->askForEntityName();
        }
    }

    protected function buildPackage(): void
    {
        info('  Whew! A new package is on the way! '.$this->emojiRocket);
        note('  '.$this->namespace.'\\'.$this->getNamespaceFromPackageName($this->packageName));
        info('  composer require '.$this->packagist.'/'.$this->getComposerNameFromPackageName($this->packageName).' will be the way.');

        $templates = config('build.package_templates');
        $templatePath = $templates[$this->name]['path'] ?? null;

        if (! $templatePath || ! is_dir(base_path($templatePath))) {
            error('Template path not found: '.$templatePath);
            exit;
        }

        $packageSlug = $this->getComposerNameFromPackageName($this->packageName);
        $targetPath = base_path('packages/'.$packageSlug);

        if (is_dir($targetPath)) {
            error('Package already exists: '.$packageSlug);
            exit;
        }

        if (! $this->copyDirectory(base_path($templatePath), $targetPath)) {
            error('Failed to copy package template');
            exit;
        }

        info('  Package files copied successfully to: '.$targetPath);
    }

    protected function copyDirectory(string $source, string $destination): bool
    {
        try {
            if (! is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $directory = opendir($source);

            while (($file = readdir($directory)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $sourcePath = $source.'/'.$file;
                $destinationPath = $destination.'/'.$file;

                if (is_dir($sourcePath)) {
                    $this->copyDirectory($sourcePath, $destinationPath);
                } else {
                    copy($sourcePath, $destinationPath);
                }
            }

            closedir($directory);

            return true;
        } catch (\Exception $e) {
            error('Error copying directory: '.$e->getMessage());

            return false;
        }
    }

    protected function getNamespaceFromPackageName(string $packageName): string
    {
        $parts = explode(' ', $packageName);
        $parts = array_map(function ($part) {
            return str_replace(['-', '_'], '', $part);
        }, $parts);

        return implode('\\', $parts);
    }

    protected function getComposerNameFromPackageName(string $packageName): string
    {
        return strtolower(str_replace(' ', '-', $packageName));
    }

    protected function buildEntity(): void
    {
        $this->info('  Building entity... '.$this->emojiRocket);

        info('  Whew! A new entity has landed! '.$this->emojiParty);
        note('  '.$this->namespace.'\\'.$this->getNamespaceFromPackageName($this->packageName).'\\'.$this->entityName);

        // Build entity, means copy the defined entity files and do the replacements
        // Show built files so that the user can review them
    }

    protected function buildPackageWithEntity(): void
    {
        $this->info('  Building package with entity... '.$this->emojiRocket);

        $this->buildPackage();
        $this->buildEntity();
    }
}
