<?php

declare(strict_types=1);

namespace Moox\Media\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaCollection;
use Moox\Media\Support\MediaPostProcessor;
use Moox\Media\Support\StaticMedia;
use Spatie\MediaLibrary\MediaCollections\FileAdderFactory;

class ImportStaticAssetsCommand extends Command
{
    protected $signature = 'media:import-static-assets
                            {brand? : Brand folder under packages/static-page (default: all configured)}
                            {--dry-run : List files without importing}
                            {--force : Re-import even when static_path already exists}';

    protected $description = 'Import static-page assets into the media library (static_path custom property)';

    public function handle(MediaPostProcessor $postProcessor): int
    {
        $root = (string) config('media.static_import.root', base_path('packages/static-page'));
        $rootReal = realpath($root);
        if ($rootReal === false || ! is_dir($rootReal)) {
            $this->error("Static import root is missing or not a directory: {$root}");

            return self::FAILURE;
        }

        /** @var list<string> $allowedBrands */
        $allowedBrands = array_values(array_filter(
            array_map(static fn (mixed $brand): string => (string) $brand, (array) config('media.static_import.brands', ['heco'])),
            static fn (string $brand): bool => $brand !== '',
        ));

        $requestedBrand = $this->argument('brand');
        if (filled($requestedBrand)) {
            $brand = (string) $requestedBrand;
            if (! in_array($brand, $allowedBrands, true)) {
                $this->error("Brand [{$brand}] is not in media.static_import.brands allowlist.");

                return self::FAILURE;
            }

            $brands = [$brand];
        } else {
            $brands = $allowedBrands;
        }

        $collection = MediaCollection::query()->first();
        if ($collection === null && ! $this->option('dry-run')) {
            $this->error('No media collection found. Create one in the admin first.');

            return self::FAILURE;
        }

        $imported = 0;
        $skipped = 0;

        foreach ($brands as $brand) {
            if (str_contains($brand, '/') || str_contains($brand, '\\') || str_contains($brand, '..')) {
                $this->warn("Skipping invalid brand name: {$brand}");

                continue;
            }

            $assetsPath = $rootReal.DIRECTORY_SEPARATOR.$brand.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'assets';
            $assetsReal = realpath($assetsPath);
            if ($assetsReal === false || ! is_dir($assetsReal)) {
                $this->warn("Skipping missing brand assets: {$assetsPath}");

                continue;
            }

            if (! str_starts_with($assetsReal, $rootReal.DIRECTORY_SEPARATOR) && $assetsReal !== $rootReal) {
                $this->warn("Skipping brand assets outside import root: {$assetsReal}");

                continue;
            }

            foreach (File::allFiles($assetsReal) as $file) {
                $fileReal = $file->getRealPath() ?: $file->getPathname();
                if (! str_starts_with($fileReal, $assetsReal.DIRECTORY_SEPARATOR)) {
                    continue;
                }

                $relative = str_replace('\\', '/', $file->getRelativePathname());
                if (str_contains($relative, '/build/') || str_starts_with($relative, 'build/')) {
                    continue;
                }

                $ext = strtolower($file->getExtension());
                if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'ico'], true)) {
                    continue;
                }

                // Skip generated responsive variants from the old optimize script.
                if (preg_match('/-(?:480|960|1440|576)\.(?:jpe?g|png|webp|avif)$/i', $relative)) {
                    continue;
                }

                $staticPath = $brand.'/'.$relative;
                $existing = StaticMedia::find($staticPath);

                if ($existing !== null && ! $this->option('force')) {
                    $skipped++;
                    $this->line("skip  {$staticPath}");

                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->line("import {$staticPath}");
                    $imported++;

                    continue;
                }

                if ($existing !== null && $this->option('force')) {
                    $existing->delete();
                }

                $absolutePath = $file->getPathname();
                $fileHash = is_file($absolutePath) ? (hash_file('sha256', $absolutePath) ?: null) : null;

                $model = new Media;
                $model->exists = true;

                /** @var Media $media */
                $media = FileAdderFactory::create($model, $absolutePath)
                    ->usingFileName($file->getFilename())
                    ->usingName(pathinfo($file->getFilename(), PATHINFO_FILENAME))
                    ->preservingOriginal()
                    ->toMediaCollection((string) ($collection->name ?? 'default'));

                $media->media_collection_id = $collection->getKey();
                $media->collection_name = (string) ($collection->name ?? 'default');
                $media->original_model_type = Media::class;
                $media->original_model_id = $media->getKey();
                $media->model_id = $media->getKey();
                $media->model_type = Media::class;
                if ($fileHash !== null) {
                    $media->setCustomProperty('file_hash', $fileHash);
                }
                StaticMedia::remember($media, $staticPath);
                $postProcessor->process($media);
                $media->save();

                $imported++;
                $this->info("ok    {$staticPath} -> #{$media->getKey()}");
            }
        }

        $this->newLine();
        $this->info("Imported: {$imported}, skipped: {$skipped}");

        return self::SUCCESS;
    }
}
