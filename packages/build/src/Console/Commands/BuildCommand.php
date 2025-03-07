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

    protected $emojiNoSee = '🙈';

    protected $emojiLink = '🔗';

    protected $emoji;

    protected $subject;

    protected $name;

    protected $type;

    protected $path;

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

    protected $website;

    public function handle()
    {
        $this->art();
        $this->info('  Welcome to the Moox Build Command!  '.$this->emojiRocket);
        $this->newLine();
        $this->info('  This command helps you build Moox Packages and Entities.');
        $this->newLine();
        $this->info('  '.$this->emojiLink.' <fg=blue;href=https://moox.org/docs/build>https://moox.org/docs/build</>');
        $this->newLine();
        $this->newLine();
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
        if ($this->subject === 'Package' || $this->subject === 'Theme') {
            $this->buildPackage();
        } else {
            $this->askForEntityName();
            if ($this->existingPackage) {
                $this->copyAndBuildEntity();
            } else {
                $this->buildPackageWithEntity();
            }
        }
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
        $packagesPath = config('build.packages_path', base_path('packages'));
        $packages = [];

        if (! is_dir($packagesPath)) {
            return $packages;
        }

        $directories = glob($packagesPath.'/*', GLOB_ONLYDIR);

        foreach ($directories as $directory) {
            $packageName = basename($directory);

            if ($packageName === 'skeleton') {
                continue;
            }

            $serviceProviderFiles = glob($directory.'/src/*ServiceProvider.php');

            foreach ($serviceProviderFiles as $file) {
                $content = file_get_contents($file);

                if (strpos($content, 'extends MooxServiceProvider') !== false ||
                    strpos($content, 'extends \Moox\Core\Providers\MooxServiceProvider') !== false) {
                    $displayName = $this->formatPackageNameForDisplay($packageName);
                    $packages[$packageName] = $displayName;
                    break;
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
        $this->sentence = $selectedTemplate['sentence'];
        $this->name = $selectedTemplate['name'];
        $this->path = $selectedTemplate['path'];
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

        info('  '.$this->motivation.' '.$this->emoji);
        info('  Let\'s build a Moox '.$this->subject.' '.$this->sentence);

        $this->website = $selectedTemplate['website'];

        info('  '.$this->emojiLink.' <fg=blue;href='.$this->website.'>'.$this->website.'</>');
        $this->newLine();
    }

    protected function askForAuthorName(): void
    {
        $this->authorName = text('What is your name?', default: config('build.default_author.name'));

        if (empty($this->authorName)) {
            error('  Please provide a valid author name. '.$this->emojiNoSee);
            $this->askForAuthorName();
        }

        info('  Hello '.$this->authorName.'! Nice to meet you. '.$this->emojiSmile);
    }

    protected function askForAuthorEmail(): void
    {
        $this->authorEmail = text('What is your email?', default: config('build.default_author.email'));

        if (empty($this->authorEmail)) {
            error('  Please provide a valid email. '.$this->emojiNoSee);
            $this->askForAuthorEmail();
        }

        info('  Great! '.$this->authorEmail.', now I can spam you. '.$this->emojiCool);
    }

    protected function askForNamespace(): void
    {
        $this->namespace = text('What is the namespace of the package?', default: config('build.default_namespace'));

        if (empty($this->namespace)) {
            error('  Please provide a valid namespace. '.$this->emojiNoSee);
            $this->askForNamespace();
        }
    }

    protected function askForPackagist(): void
    {
        $this->packagist = text('What is the packagist name of the package?', default: config('build.default_packagist'));

        if (empty($this->packagist)) {
            error('  Please provide a valid packagist name. '.$this->emojiNoSee);
            $this->askForPackagist();
        }
    }

    protected function askForPackageName(): void
    {
        $this->packageName = text('What is the name of the package?', placeholder: 'Awesome '.$this->subject);

        if (empty($this->packageName)) {
            error('  Please provide a valid package name. '.$this->emojiNoSee);
            $this->askForPackageName();
        }
    }

    protected function askForPackageDescription(): void
    {
        $this->packageDescription = $this->packageName.' is a Moox '.$this->subject.' '.$this->sentence;

        $this->packageDescription = text('What is the description of the package?', default: $this->packageDescription);

        if (empty($this->packageDescription)) {
            error('  Please provide a valid package description. '.$this->emojiNoSee);
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
            error('  Please provide a valid entity name. '.$this->emojiNoSee);
            $this->askForEntityName();
        }
    }

    protected function buildPackage(): void
    {
        $templatePath = $this->path;

        if (! $templatePath || ! is_dir(base_path($templatePath))) {
            error('  Template path not found: '.$templatePath);
            exit;
        }

        $packageSlug = $this->getComposerNameFromPackageName($this->packageName);
        $targetPath = base_path('packages/'.$packageSlug);

        if (is_dir($targetPath)) {
            error('  Package already exists: '.$packageSlug);

            $overwrite = select('What would you like to do?', [
                'delete' => 'Delete the existing package and continue',
                'exit' => 'Exit without making changes',
            ]);

            if ($overwrite === 'delete') {
                $this->deleteDirectory($targetPath);
            } else {
                exit;
            }
        }

        if (! $this->copyDirectory(base_path($templatePath), $targetPath)) {
            error('  Failed to copy package template');
            exit;
        }

        // Get template configuration
        $templates = config('build.package_templates');

        // name is not the key, it's the value
        $templateConfig = collect($templates)->firstWhere('name', $this->name);

        if (! $templateConfig) {
            error('  Template configuration not found');
            exit;
        }

        // Process file renaming
        $processedFiles = [];
        if (isset($templateConfig['rename_files']) && is_array($templateConfig['rename_files'])) {
            foreach ($templateConfig['rename_files'] as $oldPath => $newPath) {
                $oldPath = $this->replacePlaceholders($oldPath, $packageSlug);
                $newPath = $this->replacePlaceholders($newPath, $packageSlug);

                $oldFullPath = $targetPath.'/'.$oldPath;
                $newFullPath = $targetPath.'/'.$newPath;

                if (file_exists($oldFullPath)) {
                    $dirName = dirname($newFullPath);
                    if (! is_dir($dirName)) {
                        mkdir($dirName, 0755, true);
                    }

                    rename($oldFullPath, $newFullPath);
                    $processedFiles[] = $newPath;
                }
            }
        }

        // Process string replacements
        if (isset($templateConfig['replace_strings']) && is_array($templateConfig['replace_strings'])) {
            $replacedFiles = $this->processStringReplacements($targetPath, $templateConfig['replace_strings'], $packageSlug);
            $processedFiles = array_merge($processedFiles, $replacedFiles);
        }

        // Process section replacements
        if (isset($templateConfig['replace_sections']) && is_array($templateConfig['replace_sections'])) {
            $replacedSections = $this->processSectionReplacements($targetPath, $templateConfig['replace_sections'], $packageSlug);
            $processedFiles = array_merge($processedFiles, $replacedSections);
        }

        // Format and display the output
        $this->displayBuildSummary($packageSlug, $targetPath, $processedFiles);
    }

    protected function displayBuildSummary(string $packageSlug, string $targetPath, array $processedFiles): void
    {
        $this->newLine();
        note('  '.$this->emojiStar.'  Whew! A new package is on the way! '.$this->emojiParty.'  '.$this->emojiParty.'  '.$this->emojiParty);
        $this->newLine();

        $packageNamespace = $this->namespace.'\\'.$this->getNamespaceFromPackageName($this->packageName);
        $composerRequire = $this->packagist.'/'.$packageSlug;

        $this->line('       <fg=white>Package Name:      </><fg=green>'.$this->packageName.'</>');
        $this->line('       <fg=white>Namespace:         </><fg=green>'.$packageNamespace.'</>');
        $this->line('       <fg=white>Author:            </><fg=green>'.$this->authorName.'</>');
        $this->line('       <fg=white>E-Mail:            </><fg=green>'.$this->authorEmail.'</>');
        $this->line('       <fg=white>Composer:          </><fg=green>'.$composerRequire.'</>');
        $this->newLine();

        $relativePath = str_replace(base_path().'/', '', $targetPath);
        $this->line('       <fg=white>Package Path:      </><fg=blue;href=file://'.$targetPath.'>'.$relativePath.'</>');
        $this->line('       <fg=white>Package Files:</>');

        // Sort and display processed files - remove duplicates
        $uniqueFiles = array_unique($processedFiles);
        sort($uniqueFiles);
        foreach ($uniqueFiles as $file) {
            $filePath = $targetPath.'/'.$file;
            $relativeFilePath = $relativePath.'/'.$file;
            $this->line('       <fg=blue;href=file://'.$filePath.'>'.$relativeFilePath.'</>');
        }

        $this->newLine();
        $this->newLine();
        note('  '.$this->emojiRocket.'  Moox Build completed successfully! '.$this->emojiRocket.'  '.$this->emojiRocket.'  '.$this->emojiRocket);
        $this->newLine();
        $this->newLine();
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
            error('  Error copying directory: '.$e->getMessage().' '.$this->emojiNoSee);

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

    protected function copyAndBuildEntity(): void
    {
        // TODO: Here we need to copy single files as in config
        // TODO: Call the buildEntity method, I guess
    }

    protected function buildEntity(): void
    {
        error(message: '  '.$this->emojiNoSee.'  Building entity...');

        info('  Whew! A new entity has landed! '.$this->emojiParty);
        note('  '.$this->namespace.'\\'.$this->getNamespaceFromPackageName($this->packageName).'\\'.$this->entityName);

        // TODO: Build entity, means copy the defined entity files into the given package and do the replacements
        // TODO: Show built files so that the user can review them
    }

    protected function buildPackageWithEntity(): void
    {
        error(message: '  '.$this->emojiNoSee.'  Building package with entity...');

        $this->buildPackage();
        $this->buildEntity();
    }

    protected function replacePlaceholders(string $string, string $packageSlug): string
    {
        $replacements = [
            '%%packageName%%' => $this->packageName,
            '%%packageSlug%%' => $packageSlug,
            '%%authorName%%' => $this->authorName,
            '%%authorEmail%%' => $this->authorEmail,
            '%%namespace%%' => $this->namespace,
            '%%description%%' => $this->packageDescription,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $string);
    }

    protected function processStringReplacements(string $directory, array $replacements, string $packageSlug): array
    {
        $processedFiles = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $content = file_get_contents($file->getPathname());
                $originalContent = $content;

                foreach ($replacements as $search => $replace) {
                    $replace = $this->replacePlaceholders($replace, $packageSlug);
                    $content = str_replace($search, $replace, $content);
                }

                if ($content !== $originalContent) {
                    file_put_contents($file->getPathname(), $content);
                    $relativePath = str_replace($directory.'/', '', $file->getPathname());
                    $processedFiles[] = $relativePath;
                }
            }
        }

        return $processedFiles;
    }

    protected function processSectionReplacements(string $directory, array $replacements, string $packageSlug): array
    {
        $processedFiles = [];

        foreach ($replacements as $filePath => $patterns) {
            $fullPath = $directory.'/'.$filePath;

            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);
                $originalContent = $content;

                foreach ($patterns as $pattern => $replacement) {
                    $replacement = $this->replacePlaceholders($replacement, $packageSlug);
                    $content = preg_replace($pattern, $replacement, $content);
                }

                if ($content !== $originalContent) {
                    file_put_contents($fullPath, $content);
                    $processedFiles[] = $filePath;
                }
            }
        }

        return $processedFiles;
    }

    protected function deleteDirectory(string $dir): bool
    {
        if (! file_exists($dir)) {
            return true;
        }

        if (! is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (! $this->deleteDirectory($dir.DIRECTORY_SEPARATOR.$item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}
