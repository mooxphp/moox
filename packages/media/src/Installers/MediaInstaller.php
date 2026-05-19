<?php

declare(strict_types=1);

namespace Moox\Media\Installers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Installer\AbstractAssetInstaller;
use Moox\Media\Models\MediaCollection;

use function Moox\Prompts\error;
use function Moox\Prompts\note;

/**
 * Installer für das Media-Package.
 *
 * Publiziert die Spatie Media Library Konfiguration und passt sie an
 * (Moox Media-Model, CustomPathGenerator). Wird vom Moox-Installer ausgeführt.
 */
class MediaInstaller extends AbstractAssetInstaller
{
    public function getType(): string
    {
        return 'media-setup';
    }

    public function getLabel(): string
    {
        return 'Media (Spatie Media Library config + custom overrides)';
    }

    /**
     * Etwas höhere Priorität, damit Media-Setup vor anderen optionalen Schritten kommt.
     */
    protected function getDefaultConfig(): array
    {
        $config = parent::getDefaultConfig();
        $config['priority'] = 50;

        return $config;
    }

    public function hasItemSelection(): bool
    {
        return false;
    }

    public function checkExists(string $packageName, array $items): bool
    {
        $configPath = config_path('media-library.php');

        if (! File::exists($configPath)) {
            return false;
        }

        $content = File::get($configPath);

        return $this->hasMooxMediaModel($content) && $this->hasMooxPathGenerator($content);
    }

    public function install(array $assets): bool
    {
        try {
            note('📦 Publishing Spatie Media Library configuration…');

            if ($this->command) {
                $this->command->call('vendor:publish', [
                    '--provider' => 'Spatie\MediaLibrary\MediaLibraryServiceProvider',
                    '--tag' => 'medialibrary-config',
                    '--force' => $this->config['force'] ?? false,
                ]);
            } else {
                Artisan::call('vendor:publish', [
                    '--provider' => 'Spatie\MediaLibrary\MediaLibraryServiceProvider',
                    '--tag' => 'medialibrary-config',
                    '--force' => $this->config['force'] ?? false,
                ]);
            }

            $configPath = config_path('media-library.php');
            if (! File::exists($configPath)) {
                error('⚠️ media-library.php was not published.');

                return false;
            }

            $configContent = $this->applyMooxMediaLibraryConfig(File::get($configPath));

            File::put($configPath, $configContent);

            $resultConfigContent = File::get($configPath);
            $mediaModelSet = $this->hasMooxMediaModel($resultConfigContent);
            $pathGeneratorSet = $this->hasMooxPathGenerator($resultConfigContent);

            if (! $mediaModelSet || ! $pathGeneratorSet) {
                error('⚠️ media-library.php could not be overwritten correctly. Please check manually!');
            }

            note('✅ Spatie Media Library config published and updated with Moox Media model and CustomPathGenerator.');

            try {
                if (Schema::hasTable('media_collections')) {
                    MediaCollection::ensureUncategorizedExists();
                }
            } catch (\Exception $e) {
                error('⚠️ Media collection table might not exist yet: '.$e->getMessage());
            }

            return true;
        } catch (\Throwable $e) {
            error('⚠️ Media setup failed: '.$e->getMessage());

            return false;
        }
    }

    private function applyMooxMediaLibraryConfig(string $configContent): string
    {
        if (! $this->hasMooxMediaModel($configContent)) {
            $configContent = str_replace(
                'use Spatie\MediaLibrary\MediaCollections\Models\Media;',
                'use Moox\Media\Models\Media;',
                $configContent,
            );

            if (! $this->hasMooxMediaModel($configContent)) {
                $configContent = preg_replace(
                    "/(['\"]media_model['\"]\s*=>\s*)(?:\\\\?Spatie\\\\MediaLibrary\\\\MediaCollections\\\\Models\\\\)?Media::class/",
                    '$1\\Moox\\Media\\Models\\Media::class',
                    $configContent,
                    1,
                ) ?? $configContent;
            }
        }

        if (! $this->hasMooxPathGenerator($configContent)) {
            $configContent = str_replace(
                'use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;',
                'use Moox\Media\Support\CustomPathGenerator;',
                $configContent,
            );

            $configContent = str_replace(
                "'path_generator' => DefaultPathGenerator::class",
                "'path_generator' => CustomPathGenerator::class",
                $configContent,
            );

            $configContent = str_replace(
                '"path_generator" => DefaultPathGenerator::class',
                '"path_generator" => CustomPathGenerator::class',
                $configContent,
            );

            if (! $this->hasMooxPathGenerator($configContent)) {
                $configContent = preg_replace(
                    "/(['\"]path_generator['\"]\s*=>\s*)(?:\\\\?Spatie\\\\MediaLibrary\\\\Support\\\\PathGenerator\\\\)?DefaultPathGenerator::class/",
                    '$1\\Moox\\Media\\Support\\CustomPathGenerator::class',
                    $configContent,
                    1,
                ) ?? $configContent;
            }
        }

        return $configContent;
    }

    private function hasMooxMediaModel(string $content): bool
    {
        if (str_contains($content, 'use Moox\Media\Models\Media;')) {
            return true;
        }

        return (bool) preg_match(
            '/[\'"]media_model[\'"]\s*=>\s*\\\\?Moox\\\\Media\\\\Models\\\\Media::class/',
            $content,
        );
    }

    private function hasMooxPathGenerator(string $content): bool
    {
        if (str_contains($content, 'use Moox\Media\Support\CustomPathGenerator;')) {
            return true;
        }

        return (bool) preg_match(
            '/[\'"]path_generator[\'"]\s*=>\s*\\\\?Moox\\\\Media\\\\Support\\\\CustomPathGenerator::class/',
            $content,
        );
    }
}
