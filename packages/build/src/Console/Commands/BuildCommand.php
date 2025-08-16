<?php

namespace Moox\Build\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Moox\Core\Console\Traits\ArtLeft;
use RuntimeException;

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

    protected $name;

    protected $path;

    protected $packageName;

    protected $authorName;

    protected $authorEmail;

    protected $namespace;

    protected $packagist;

    protected $packageDescription;

    protected $website;

    public function handle()
    {
        $this->art();
        $this->info('  Welcome to the Moox Build Command!  🚀');
        $this->newLine();
        $this->info('  This command helps you build Moox Packages.');
        $this->newLine();
        $this->info('  🔗 <fg=blue;href=https://moox.org/package_templates>https://moox.org/docs/build</>');
        $this->newLine();
        $this->newLine();

        $this->askForPackageType();
        $this->askForAuthorName();
        $this->askForAuthorEmail();
        $this->askForNamespace();
        $this->askForPackagist();
        $this->askForPackageName();
        $this->askForPackageDescription();

        $this->buildPackage();
    }

    protected function askForPackageType(): void
    {
        $templates = config('build.package_templates');

        $choices = collect($templates)->mapWithKeys(fn ($template, $key) => [
            $key => $template['name'].' - '.$template['select'],
        ])->toArray();

        $type = select('What Type of Package do you want to build?', $choices, scroll: 10);

        $selectedTemplate = $templates[$type] ?? null;
        $selectedTemplate['key'] = $type;

        if (! $this->templateExists($selectedTemplate)) {
            $this->installTemplate($selectedTemplate);
        }

        $this->name = $selectedTemplate['name'];
        $this->path = $this->getTemplatePath($selectedTemplate);
        $this->website = $selectedTemplate['website'];

        info('  Let\'s build a new awesome Moox package using '.$selectedTemplate['name'].'.');
        info('  🔗 <fg=blue;href='.$this->website.'>'.$this->website.'</>');
        $this->newLine();
    }

    protected function templateExists(array $template): bool
    {
        return is_dir(base_path('packages/'.$template['key'])) ||
               is_dir(base_path('vendor/'.$template['composer']));
    }

    protected function installTemplate(array $template): void
    {
        info("  Installing template: {$template['name']}");

        $result = shell_exec("composer require {$template['composer']} 2>&1; echo $?");

        if ((int) $result === 0) {
            info('  Template installed successfully!');
        } else {
            error('  Failed to install template. Please install manually:');
            error("  composer require {$template['composer']}");
            exit(1);
        }
    }

    protected function getTemplatePath(array $template): string
    {
        if (is_dir(base_path('packages/'.$template['key']))) {
            return base_path('packages/'.$template['key']);
        }

        if (is_dir(base_path('vendor/'.$template['composer']))) {
            return base_path('vendor/'.$template['composer']);
        }

        throw new RuntimeException("Template not found: {$template['key']}");
    }

    protected function askForAuthorName(): void
    {
        $this->authorName = text('What is your name?', default: config('build.default_author.name'));

        if (empty($this->authorName)) {
            error('  Please provide a valid author name. 🙈');
            $this->askForAuthorName();
        }

        info('  Hello '.$this->authorName.'! Nice to meet you. 😊');
    }

    protected function askForAuthorEmail(): void
    {
        $this->authorEmail = text('What is your email?', default: config('build.default_author.email'));

        if (empty($this->authorEmail)) {
            error('  Please provide a valid email. 🙈');
            $this->askForAuthorEmail();
        }

        info('  Great! '.$this->authorEmail.', now I can spam you. 😎');
    }

    protected function askForNamespace(): void
    {
        $this->namespace = text('What is the vendor namespace of the package?', default: config('build.default_namespace'));

        if (empty($this->namespace)) {
            error('  Please provide a valid namespace. 🙈');
            $this->askForNamespace();
        }
    }

    protected function askForPackagist(): void
    {
        $this->packagist = text('What is the packagist organization name of the package?', default: config('build.default_packagist'));

        if (empty($this->packagist)) {
            error('  Please provide a valid packagist name. 🙈');
            $this->askForPackagist();
        }
    }

    protected function askForPackageName(): void
    {
        $this->packageName = text('What is the name of the package?', placeholder: 'Awesome ');

        if (empty($this->packageName)) {
            error('  Please provide a valid package name. 🙈');
            $this->askForPackageName();
        }
    }

    protected function askForPackageDescription(): void
    {
        $this->packageDescription = $this->packageName.' is a Moox package.';
        $this->packageDescription = text('What is the description of the package?', default: $this->packageDescription);

        if (empty($this->packageDescription)) {
            error('  Please provide a valid package description. 🙈');
            $this->askForPackageDescription();
        }
    }

    protected function buildPackage(): void
    {
        if (! $this->path || ! is_dir($this->path)) {
            error('  Template path not found: '.$this->path);
            exit(1);
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

        if (! $this->copyDirectory($this->path, $targetPath)) {
            error('  Failed to copy package template');
            exit(1);
        }

        $this->replacePlaceholdersInFiles($targetPath, $packageSlug);
        $this->displayBuildSummary($packageSlug, $targetPath);
    }

    protected function copyDirectory(string $source, string $destination): bool
    {
        try {
            if (! is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $target = $destination.DIRECTORY_SEPARATOR.$iterator->getSubPathName();

                if ($item->isDir()) {
                    if (! is_dir($target)) {
                        mkdir($target, 0755, true);
                    }
                } else {
                    copy($item, $target);
                }
            }

            return true;
        } catch (Exception $e) {
            error('  Error copying directory: '.$e->getMessage().' 🙈');

            return false;
        }
    }

    protected function replacePlaceholdersInFiles(string $targetPath, string $packageSlug): void
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

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($targetPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isFile() && $this->isTextFile($item->getPathname())) {
                $content = file_get_contents($item->getPathname());
                $newContent = str_replace(array_keys($replacements), array_values($replacements), $content);

                if ($content !== $newContent) {
                    file_put_contents($item->getPathname(), $newContent);
                }
            }
        }
    }

    protected function isTextFile(string $file): bool
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $binaryExtensions = ['png', 'jpg', 'jpeg', 'gif', 'ico', 'zip', 'pdf', 'exe', 'dll'];

        return ! in_array(strtolower($extension), $binaryExtensions);
    }

    protected function getComposerNameFromPackageName(string $packageName): string
    {
        return strtolower(str_replace(' ', '-', $packageName));
    }

    protected function displayBuildSummary(string $packageSlug, string $targetPath): void
    {
        $this->newLine();
        note('  ⭐  Whew! A new package is on the way! 🎉  🎉  🎉');
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
        $this->newLine();

        note('  🚀  Moox Build completed successfully! 🚀  🚀  🚀');
        $this->newLine();
    }

    protected function getNamespaceFromPackageName(string $packageName): string
    {
        $parts = explode(' ', $packageName);
        $parts = array_map(function ($part) {
            return str_replace(['-', '_'], '', $part);
        }, $parts);

        return implode('\\', $parts);
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
