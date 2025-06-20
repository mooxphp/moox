<?php

namespace Moox\Build\Console\Commands;

use RuntimeException;
use Throwable;
use ReflectionClass;
use ReflectionMethod;
use Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use finfo;
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
            throw new RuntimeException('Selected template not found');
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

        // Get the service provider from the template package
        $templatePackageName = basename($templatePath);
        $serviceProvider = $this->getServiceProviderFromPackage($templatePackageName);

        if (! $serviceProvider) {
            error('  Service provider not found for template: '.$templatePackageName);
            exit;
        }

        // Initialize the service provider
        $serviceProvider->register();
        $serviceProvider->boot();

        // Get the MooxPackage
        $mooxPackage = $serviceProvider->getMooxPackage();

        // Process file renaming from service provider
        $processedFiles = [];

        // Get rename configuration from MooxPackage
        $renameConfig = $mooxPackage->getTemplateRename();

        if (! empty($renameConfig)) {
            foreach ($renameConfig as $pattern => $replacement) {
                $renamedFiles = $this->processFileRenaming($targetPath, $pattern, $replacement, $packageSlug);
                $processedFiles = array_merge($processedFiles, $renamedFiles);
            }
        }

        // Get string replacements from MooxPackage
        $replacements = $mooxPackage->getTemplateReplace();

        if (! empty($replacements)) {
            $replacedFiles = $this->processStringReplacements($targetPath, $replacements, $packageSlug);
            $processedFiles = array_merge($processedFiles, $replacedFiles);
        }

        // Get section replacements from MooxPackage
        $sectionReplacements = $mooxPackage->getTemplateSectionReplace();

        if (! empty($sectionReplacements)) {
            $replacedSections = $this->processSectionReplacements($targetPath, $sectionReplacements, $packageSlug);
            $processedFiles = array_merge($processedFiles, $replacedSections);
        }

        // Format and display the output
        $this->displayBuildSummary($packageSlug, $targetPath, $processedFiles);

        // Remove template configuration from the new package's service provider
        $this->removeTemplateConfigFromServiceProvider($targetPath);
    }

    protected function getServiceProviderFromPackage(string $packageName): ?object
    {
        $serviceProviderFiles = glob(base_path('packages/'.$packageName.'/src/*ServiceProvider.php'));

        foreach ($serviceProviderFiles as $file) {
            $content = file_get_contents($file);
            $namespace = $this->extractNamespace($content);
            $className = $this->extractClassName($file);

            if (empty($namespace) || empty($className)) {
                continue;
            }

            $fullyQualifiedClassName = $namespace.'\\'.$className;

            try {
                if (! class_exists($fullyQualifiedClassName)) {
                    require_once $file;
                }

                return new $fullyQualifiedClassName(app());
            } catch (Throwable $e) {
                $this->line('Debug: Error loading provider: '.$e->getMessage());
            }
        }

        return null;
    }

    protected function getTemplateRenameFromProvider(object $provider): array
    {
        if (method_exists($provider, 'getMooxPackage')) {
            $mooxPackage = $provider->getMooxPackage();

            // If we have a mooxPackage, check if it has a getTemplateRename method
            if ($mooxPackage && method_exists($mooxPackage, 'getTemplateRename')) {
                $renameConfig = $mooxPackage->getTemplateRename();

                return $renameConfig;
            }
        }

        // If we couldn't get the config through methods, try reflection
        try {
            $reflection = new ReflectionClass($provider);

            // Try to find a method that returns the templateRename configuration
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                $methodName = $method->getName();
                if (strpos($methodName, 'getTemplateRename') === 0) {
                    return $provider->$methodName();
                }
            }

            // If no method found, try to access the property directly
            if ($reflection->hasProperty('mooxPackage')) {
                $mooxPackageProperty = $reflection->getProperty('mooxPackage');
                $mooxPackageProperty->setAccessible(true);
                $mooxPackage = $mooxPackageProperty->getValue($provider);

                if ($mooxPackage) {
                    $mooxPackageReflection = new ReflectionClass($mooxPackage);
                    if ($mooxPackageReflection->hasProperty('templateRename')) {
                        $templateRenameProperty = $mooxPackageReflection->getProperty('templateRename');
                        $templateRenameProperty->setAccessible(true);

                        return $templateRenameProperty->getValue($mooxPackage) ?? [];
                    }
                }
            }
        } catch (Throwable $e) {
            $this->line('Debug: Reflection error: '.$e->getMessage());
        }

        return [];
    }

    protected function getTemplateReplacementsFromProvider(object $provider): array
    {
        // Check if the provider has a getMooxPackage method
        if (method_exists($provider, 'getMooxPackage')) {
            $mooxPackage = $provider->getMooxPackage();

            // If we have a mooxPackage, check if it has a getTemplateReplace method
            if ($mooxPackage && method_exists($mooxPackage, 'getTemplateReplace')) {
                return $mooxPackage->getTemplateReplace();
            }
        }

        // If we couldn't get the config through methods, try reflection
        try {
            $reflection = new ReflectionClass($provider);

            // Try to find a method that returns the templateReplace configuration
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                $methodName = $method->getName();
                if (strpos($methodName, 'getTemplateReplace') === 0) {
                    return $provider->$methodName();
                }
            }

            // If no method found, try to access the property directly
            if ($reflection->hasProperty('mooxPackage')) {
                $mooxPackageProperty = $reflection->getProperty('mooxPackage');
                $mooxPackageProperty->setAccessible(true);
                $mooxPackage = $mooxPackageProperty->getValue($provider);

                if ($mooxPackage) {
                    $mooxPackageReflection = new ReflectionClass($mooxPackage);
                    if ($mooxPackageReflection->hasProperty('templateReplace')) {
                        $templateReplaceProperty = $mooxPackageReflection->getProperty('templateReplace');
                        $templateReplaceProperty->setAccessible(true);

                        return $templateReplaceProperty->getValue($mooxPackage) ?? [];
                    }
                }
            }
        } catch (Throwable $e) {
            $this->line('Debug: Reflection error: '.$e->getMessage());
        }

        return [];
    }

    protected function getTemplateSectionReplacementsFromProvider(object $provider): array
    {
        // Check if the provider has a getMooxPackage method
        if (method_exists($provider, 'getMooxPackage')) {
            $mooxPackage = $provider->getMooxPackage();

            // If we have a mooxPackage, check if it has a getTemplateSectionReplace method
            if ($mooxPackage && method_exists($mooxPackage, 'getTemplateSectionReplace')) {
                return $mooxPackage->getTemplateSectionReplace();
            }
        }

        // If we couldn't get the config through methods, try reflection
        try {
            $reflection = new ReflectionClass($provider);

            // Try to find a method that returns the templateSectionReplace configuration
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                $methodName = $method->getName();
                if (strpos($methodName, 'getTemplateSectionReplace') === 0) {
                    return $provider->$methodName();
                }
            }

            // If no method found, try to access the property directly
            if ($reflection->hasProperty('mooxPackage')) {
                $mooxPackageProperty = $reflection->getProperty('mooxPackage');
                $mooxPackageProperty->setAccessible(true);
                $mooxPackage = $mooxPackageProperty->getValue($provider);

                if ($mooxPackage) {
                    $mooxPackageReflection = new ReflectionClass($mooxPackage);
                    if ($mooxPackageReflection->hasProperty('templateSectionReplace')) {
                        $templateSectionReplaceProperty = $mooxPackageReflection->getProperty('templateSectionReplace');
                        $templateSectionReplaceProperty->setAccessible(true);

                        return $templateSectionReplaceProperty->getValue($mooxPackage) ?? [];
                    }
                }
            }
        } catch (Throwable $e) {
            $this->line('Debug: Reflection error: '.$e->getMessage());
        }

        return [];
    }

    protected function extractNamespace(string $content): string
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return $matches[1];
        }

        return '';
    }

    protected function extractClassName(string $file): string
    {
        $filename = basename($file);

        return pathinfo($filename, PATHINFO_FILENAME);
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
        $this->line('       <fg=white>Package Path:      </><fg=green>'.$relativePath.'</>');
        $this->line('       <fg=white>Package Files:     </><fg=green></>');

        // Extract file paths from processed files array
        $uniqueFilePaths = [];
        foreach ($processedFiles as $processedFile) {
            if (isset($processedFile['file'])) {
                $uniqueFilePaths[] = $processedFile['file'];
            } elseif (isset($processedFile['new'])) {
                $uniqueFilePaths[] = $processedFile['new'];
            }
        }

        // Remove duplicates and sort
        $uniqueFilePaths = array_unique($uniqueFilePaths);
        sort($uniqueFilePaths);

        // Display files
        foreach ($uniqueFilePaths as $filePath) {
            $relativeFilePath = str_replace($targetPath.'/', '', $filePath);
            $this->line('       <fg=white>                   </><fg=green>'.$relativeFilePath.'</>');
        }

        note('  '.$this->emojiRocket.'  Moox Build completed successfully! '.$this->emojiRocket.'  '.$this->emojiRocket.'  '.$this->emojiRocket);
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
        } catch (Exception $e) {
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

    protected function processFileRenaming(string $targetPath, string $pattern, string $replacement, string $packageSlug): array
    {
        $processedFiles = [];

        // Replace placeholders in the replacement string
        $replacement = $this->replacePlaceholders($replacement, $packageSlug);

        // Find all files in the target directory
        $files = $this->findAllFiles($targetPath);

        foreach ($files as $file) {
            $originalName = $file;
            $newName = str_replace($pattern, $replacement, $originalName);

            if ($originalName !== $newName) {
                // Create the directory if it doesn't exist
                $newDir = dirname($newName);
                if (! is_dir($newDir)) {
                    mkdir($newDir, 0755, true);
                }

                // Rename the file
                if (rename($originalName, $newName)) {
                    $processedFiles[] = [
                        'type' => 'rename',
                        'original' => $originalName,
                        'new' => $newName,
                    ];
                }
            }
        }

        return $processedFiles;
    }

    protected function processStringReplacements(string $targetPath, array $replacements, string $packageSlug): array
    {
        $processedFiles = [];

        // Find all files in the target directory
        $files = $this->findAllFiles($targetPath);

        foreach ($files as $file) {
            // Skip binary files
            if ($this->isBinaryFile($file)) {
                continue;
            }

            $content = file_get_contents($file);
            $originalContent = $content;

            foreach ($replacements as $search => $replace) {
                // Replace placeholders in the replacement string
                $replace = $this->replacePlaceholders($replace, $packageSlug);

                // Perform the replacement
                $content = str_replace($search, $replace, $content);
            }

            if ($content !== $originalContent) {
                file_put_contents($file, $content);
                $processedFiles[] = [
                    'type' => 'replace',
                    'file' => $file,
                ];
            }
        }

        return $processedFiles;
    }

    protected function processSectionReplacements(string $targetPath, array $sectionReplacements, string $packageSlug): array
    {
        $processedFiles = [];

        // Find all files in the target directory
        $files = $this->findAllFiles($targetPath);

        foreach ($files as $file) {
            // Skip binary files
            if ($this->isBinaryFile($file)) {
                continue;
            }

            $content = file_get_contents($file);
            $originalContent = $content;

            foreach ($sectionReplacements as $pattern => $replacement) {
                // Replace placeholders in the replacement string
                $replacement = $this->replacePlaceholders($replacement, $packageSlug);

                // Perform the replacement using regex
                $content = preg_replace($pattern, $replacement, $content);
            }

            if ($content !== $originalContent) {
                file_put_contents($file, $content);
                $processedFiles[] = [
                    'type' => 'section_replace',
                    'file' => $file,
                ];
            }
        }

        return $processedFiles;
    }

    protected function findAllFiles(string $directory): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    protected function isBinaryFile(string $file): bool
    {
        // Check file extension
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $binaryExtensions = ['png', 'jpg', 'jpeg', 'gif', 'ico', 'zip', 'pdf', 'exe', 'dll'];

        if (in_array(strtolower($extension), $binaryExtensions)) {
            return true;
        }

        // Check file content
        $finfo = new finfo(FILEINFO_MIME);
        $mime = $finfo->file($file);

        return strpos($mime, 'text/') !== 0 && strpos($mime, 'application/json') !== 0;
    }

    protected function replacePlaceholders(string $text, string $packageSlug): string
    {
        $replacements = [
            '%%PackageName%%' => $this->packageName,
            '%%PackageSlug%%' => $packageSlug,
            '%%Description%%' => $this->packageDescription,
            '%%AuthorName%%' => $this->authorName,
            '%%AuthorEmail%%' => $this->authorEmail,
            '%%Namespace%%' => $this->namespace,
            '%%Packagist%%' => $this->packagist,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
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

    protected function removeTemplateConfigFromServiceProvider(string $packagePath): void
    {
        $serviceProviderFiles = glob($packagePath.'/src/*ServiceProvider.php');

        foreach ($serviceProviderFiles as $file) {
            $content = file_get_contents($file);

            // Remove template configuration methods
            $patterns = [
                // Remove templateRename calls
                '/->templateRename\(\s*\[.*?\]\s*\)/s' => '',

                // Remove templateReplace calls
                '/->templateReplace\(\s*\[.*?\]\s*\)/s' => '',

                // Remove templateSectionReplace calls
                '/->templateSectionReplace\(\s*\[.*?\]\s*\)/s' => '',

                // Remove any empty chain calls that might be left (e.g., "->->")
                '/->->/s' => '->',
            ];

            $newContent = preg_replace(array_keys($patterns), array_values($patterns), $content);

            // Write the updated content back to the file
            if ($newContent !== $content) {
                file_put_contents($file, $newContent);
            }
        }
    }
}
