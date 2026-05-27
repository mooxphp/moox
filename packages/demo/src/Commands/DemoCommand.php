<?php

declare(strict_types=1);

namespace Moox\Demo\Commands;

use Illuminate\Console\Command;
use Moox\Demo\Demo\DemoContext;
use Moox\Demo\Demo\DemoRunner;

class DemoCommand extends Command
{
    protected $signature = 'moox:demo
        {--languages=3 : Number of localizations when --locales is omitted}
        {--locales= : Comma-separated locale variants (e.g. de_DE,en_US,es_ES)}
        {--dataset=small : Dataset size: small, medium, large, huge}
        {--fresh : Run migrate:fresh before seeding}
        {--skip-seeders : Skip package seeders}
        {--skip-factories : Skip factory-based entity seeding}
        {--skip-media : Skip demo media file import}';

    protected $description = 'Seed demo data for installed Moox packages (localizations, seeders, factories, media)';

    public function handle(): int
    {
        $dataset = (string) $this->option('dataset');
        $sizes = config('demo.dataset_sizes', []);
        $datasetCount = $sizes[$dataset] ?? $sizes[config('demo.default_dataset', 'small')] ?? 100;

        $locales = $this->resolveLocales();

        $context = new DemoContext(
            languageCount: (int) $this->option('languages'),
            locales: $locales,
            dataset: $dataset,
            datasetCount: $datasetCount,
            fresh: (bool) $this->option('fresh'),
            skipSeeders: (bool) $this->option('skip-seeders'),
            skipFactories: (bool) $this->option('skip-factories'),
            skipMedia: (bool) $this->option('skip-media'),
        );

        return (new DemoRunner($this))->run($context);
    }

    /**
     * @return list<string>
     */
    private function resolveLocales(): array
    {
        $localesOption = $this->option('locales');

        if (is_string($localesOption) && $localesOption !== '') {
            return array_values(array_filter(array_map(
                static fn (string $value): string => trim($value),
                explode(',', $localesOption)
            )));
        }

        $defaults = config('demo.default_locales', ['de_DE', 'en_US', 'es_ES']);
        $count = max(1, (int) $this->option('languages'));

        return array_slice($defaults, 0, $count);
    }
}
