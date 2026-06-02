<?php

declare(strict_types=1);

namespace Moox\Demo\Demo\Steps;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Moox\Demo\Console\DemoConsole;
use Moox\Demo\Demo\DemoContext;
use Moox\Demo\Support\MooxPackageDiscovery;

final class FactoryEntitiesStep
{
    public function __construct(
        private readonly DemoConsole $console,
    ) {}

    public function run(DemoContext $context): void
    {
        if ($context->skipFactories) {
            $this->console->skip('Factory seeding', 'skipped via --skip-factories');

            return;
        }

        config([
            'demo.locales' => $context->locales,
            'demo.dataset_count' => $context->datasetCount,
        ]);

        foreach (MooxPackageDiscovery::mooxPackageNames() as $packageName) {
            $composer = MooxPackageDiscovery::readComposerJson($packageName);

            if ($composer === null) {
                continue;
            }

            $entities = $composer['extra']['moox']['install']['auto_entities'] ?? [];
            $classes = $composer['extra']['moox']['install']['auto_class'] ?? [];

            foreach ($entities as $entityName => $enabled) {
                if (! $enabled) {
                    continue;
                }

                $modelClass = $classes[$entityName] ?? null;

                if (! is_string($modelClass) || ! class_exists($modelClass)) {
                    continue;
                }

                if (! is_subclass_of($modelClass, Model::class)) {
                    continue;
                }

                if (! $this->modelUsesFactory($modelClass)) {
                    continue;
                }

                $factory = $this->resolveFactory($modelClass);

                if ($factory === null) {
                    continue;
                }

                $label = class_basename($modelClass);

                try {
                    $created = $this->seedWithFactory($factory, $context, $label);
                    $this->console->finishTask("{$packageName} · {$label}", "{$created} record(s)");
                } catch (\Throwable $e) {
                    $this->console->failTask("{$packageName} · {$label}", $e->getMessage());
                }
            }
        }
    }

    private function modelUsesFactory(string $modelClass): bool
    {
        return in_array(
            HasFactory::class,
            class_uses_recursive($modelClass),
            true
        );
    }

    private function resolveFactory(string $modelClass): ?Factory
    {
        if (! method_exists($modelClass, 'factory')) {
            return null;
        }

        return $modelClass::factory();
    }

    private function seedWithFactory(Factory $factory, DemoContext $context, string $label): int
    {
        $count = $context->datasetCount;
        $locales = $context->locales;
        $progress = $this->console->progressBar($count, $label);

        if ($locales !== [] && method_exists($factory, 'withLocales')) {
            for ($i = 0; $i < $count; $i++) {
                $factory->withLocales($locales)->create();
                $progress->advance();
            }

            $progress->finish("{$count} record(s)");

            return $count;
        }

        if ($locales !== [] && method_exists($factory, 'withTranslationLocales')) {
            for ($i = 0; $i < $count; $i++) {
                $factory->withTranslationLocales(...$locales)->create();
                $progress->advance();
            }

            $progress->finish("{$count} record(s)");

            return $count;
        }

        for ($i = 0; $i < $count; $i++) {
            $factory->create();
            $progress->advance();
        }

        $progress->finish("{$count} record(s)");

        return $count;
    }
}
