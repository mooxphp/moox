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

        return str_contains($content, 'Moox\\Media\\Models\\Media::class');
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

            $configContent = File::get($configPath);

            $configContent = str_replace(
                "'media_model' => Spatie\\MediaLibrary\\MediaCollections\\Models\\Media::class",
                "'media_model' => Moox\\Media\\Models\\Media::class",
                $configContent
            );

            $configContent = str_replace(
                "'path_generator' => Spatie\\MediaLibrary\\Support\\PathGenerator\\DefaultPathGenerator::class",
                "'path_generator' => Moox\\Media\\Support\\CustomPathGenerator::class",
                $configContent
            );

            File::put($configPath, $configContent);

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
}
