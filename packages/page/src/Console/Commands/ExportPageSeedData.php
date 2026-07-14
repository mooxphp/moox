<?php

declare(strict_types=1);

namespace Moox\Page\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Moox\Page\Models\Page;
use Moox\Page\Support\PageModels;

#[Signature('pages:export-seed-data {--path=}')]
#[Description('Export current pages to a PHP seed data file')]
class ExportPageSeedData extends Command
{
    public function handle(): int
    {
        $path = $this->option('path')
            ?? dirname(__DIR__, 3).'/database/seeders/data/pages.php';

        $pages = PageModels::page()::query()->with('translations')->orderBy('id')->get();

        $export = $pages->map(function (Page $page): array {
            return [
                'page' => collect($page->getAttributes())->only([
                    'is_active',
                    'is_startpage',
                    'image',
                    'layout',
                    'uuid',
                    'ulid',
                ])->filter(fn (mixed $value): bool => $value !== null)->all(),
                'translations' => $page->translations->map(function ($translation): array {
                    return [
                        'locale' => $translation->locale,
                        'title' => $translation->title,
                        'slug' => $translation->slug,
                        'permalink' => $translation->permalink,
                        'description' => $translation->description,
                        'content' => $translation->content,
                        'translation_status' => $translation->translation_status,
                        'published_at' => $translation->published_at,
                        'to_publish_at' => $translation->to_publish_at,
                        'to_unpublish_at' => $translation->to_unpublish_at,
                        'unpublished_at' => $translation->unpublished_at,
                        'author_id' => $translation->author_id,
                        'author_type' => $translation->author_type,
                    ];
                })->map(
                    fn (array $attributes): array => collect($attributes)
                        ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
                        ->all()
                )->values()->all(),
            ];
        })->values()->all();

        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, '<?php'.PHP_EOL.PHP_EOL.'declare(strict_types=1);'.PHP_EOL.PHP_EOL.'return '.var_export($export, true).';'.PHP_EOL);

        $this->components->info('Exported '.count($export)." page(s) to {$path}");

        return self::SUCCESS;
    }
}
