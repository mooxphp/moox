<?php

declare(strict_types=1);

namespace Moox\Page\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Moox\Page\Support\PageModels;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = __DIR__.'/data/pages.php';

        if (! is_file($dataPath)) {
            return;
        }

        /** @var mixed $pages */
        $pages = require $dataPath;

        if (! is_array($pages)) {
            return;
        }

        foreach ($pages as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $pageAttributes = $entry['page'] ?? null;
            $translations = $entry['translations'] ?? null;

            if (! is_array($pageAttributes) || ! is_array($translations)) {
                continue;
            }

            if ($this->entryAlreadySeeded($translations)) {
                continue;
            }

            unset($pageAttributes['uuid'], $pageAttributes['ulid']);

            if (array_key_exists('is_active', $pageAttributes)) {
                $pageAttributes['is_active'] = (bool) $pageAttributes['is_active'];
            }

            if (array_key_exists('is_startpage', $pageAttributes)) {
                $pageAttributes['is_startpage'] = (bool) $pageAttributes['is_startpage'];
            }

            $pageAttributes = collect($pageAttributes)
                ->filter(fn (mixed $value, string $column): bool => Schema::hasColumn('pages', $column))
                ->all();

            $page = PageModels::page()::query()->create($pageAttributes);

            foreach ($translations as $translationAttributes) {
                if (! is_array($translationAttributes)) {
                    continue;
                }

                if (isset($translationAttributes['content']) && is_string($translationAttributes['content'])) {
                    $decodedContent = json_decode($translationAttributes['content'], true);
                    $translationAttributes['content'] = is_array($decodedContent) ? $decodedContent : [];
                }

                if (isset($translationAttributes['author_id'], $translationAttributes['author_type'])) {
                    $authorType = $translationAttributes['author_type'];
                    $authorExists = false;

                    if (
                        is_string($authorType)
                        && class_exists($authorType)
                        && Schema::hasTable((new $authorType)->getTable())
                    ) {
                        $authorExists = $authorType::query()
                            ->whereKey($translationAttributes['author_id'])
                            ->exists();
                    }

                    if (! $authorExists) {
                        unset($translationAttributes['author_id'], $translationAttributes['author_type']);
                    }
                }

                $translationAttributes = collect($translationAttributes)
                    ->filter(fn (mixed $value, string $column): bool => Schema::hasColumn('page_translations', $column))
                    ->all();

                $page->translations()->create($translationAttributes);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $translations
     */
    private function entryAlreadySeeded(array $translations): bool
    {
        foreach ($translations as $translationAttributes) {
            if (! is_array($translationAttributes)) {
                continue;
            }

            $slug = $translationAttributes['slug'] ?? null;
            $locale = $translationAttributes['locale'] ?? null;

            if (! is_string($slug) || ! is_string($locale)) {
                continue;
            }

            if (PageModels::pageTranslation()::query()->where('slug', $slug)->where('locale', $locale)->exists()) {
                return true;
            }
        }

        return false;
    }
}
