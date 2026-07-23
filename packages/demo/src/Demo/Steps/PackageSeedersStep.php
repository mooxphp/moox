<?php

declare(strict_types=1);

namespace Moox\Demo\Demo\Steps;

use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Moox\Demo\Console\DemoConsole;
use Moox\Demo\Demo\DemoContext;
use Moox\Demo\Demo\SeederOrderResolver;
use Moox\Demo\Support\MooxPackageDiscovery;
use Moox\Localization\Models\Localization;

final class PackageSeedersStep
{
    public function __construct(
        private readonly Command $command,
        private readonly SeederOrderResolver $resolver,
        private readonly DemoConsole $console,
    ) {
    }

    /**
     * @param  list<string>|null  $onlySlugs
     * @param  list<string>  $exceptSlugs
     */
    public function run(DemoContext $context, ?array $onlySlugs = null, array $exceptSlugs = []): void
    {
        if ($context->skipSeeders) {
            $this->console->skip('Package seeders', 'skipped via --skip-seeders');

            return;
        }

        $failedSlugs = [];
        $ordered = $this->resolver->resolve();

        foreach ($ordered as $item) {
            $slug = $item['slug'];

            if ($onlySlugs !== null && ! in_array($slug, $onlySlugs, true)) {
                continue;
            }

            if (in_array($slug, $exceptSlugs, true)) {
                continue;
            }

            foreach ($failedSlugs as $failed) {
                if ($this->dependsOn($slug, $failed)) {
                    $this->console->skip(
                        $item['package'],
                        "dependency {$failed} failed"
                    );

                    continue 2;
                }
            }

            $class = $item['class'];

            if ($class === null || ! class_exists($class)) {
                $this->console->skip(
                    $item['package'],
                    "seeder not found ({$item['seeder']})"
                );

                continue;
            }

            if ($this->shouldSkipSlugForLocalization($slug, $context)) {
                if ($this->command->getOutput()->isVerbose()) {
                    $this->console->skip($item['package'], 'using Demo localization step');
                }

                continue;
            }

            $label = "{$item['package']} · {$item['seeder']}";

            try {
                $this->console->beginNestedOutput($label);
                $this->invokeSeeder($class);
                $this->console->finishTask($label);
            } catch (\Throwable $e) {
                $failedSlugs[] = $slug;
                $this->console->failTask($label, $e->getMessage());
            }
        }
    }

    private function invokeSeeder(string $class): void
    {
        /** @var Seeder $seeder */
        $seeder = app()->make($class);
        $seeder->setContainer(app());
        $seeder->setCommand($this->command);
        $seeder->__invoke();
    }

    private function shouldSkipSlugForLocalization(string $slug, DemoContext $context): bool
    {
        return $slug === 'localization'
            && class_exists(Localization::class)
            && $context->locales !== [];
    }

    private function dependsOn(string $slug, string $failedSlug): bool
    {
        $graph = MooxPackageDiscovery::mooxDependencyGraph();
        $visited = [];
        $stack = [$slug];

        while ($stack !== []) {
            $current = array_pop($stack);
            if ($current === $failedSlug) {
                return true;
            }

            if (isset($visited[$current])) {
                continue;
            }

            $visited[$current] = true;

            foreach ($graph[$current] ?? [] as $dep) {
                $stack[] = $dep;
            }
        }

        return false;
    }
}
