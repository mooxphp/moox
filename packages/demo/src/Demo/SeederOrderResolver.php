<?php

declare(strict_types=1);

namespace Moox\Demo\Demo;

use Moox\Demo\Support\MooxPackageDiscovery;

final class SeederOrderResolver
{
    /**
     * @return list<array{package: string, slug: string, class: string|null, seeder: string}>
     */
    public function resolve(): array
    {
        $providers = MooxPackageDiscovery::scanMooxProviders();
        $skipSlugs = config('demo.seeder_skip', []);
        $nestedBasenames = config('demo.nested_seeder_basenames', []);
        $priority = array_flip(config('demo.seeder_order', []));

        $candidates = [];

        foreach ($providers as $packageName => $providerClass) {
            $slug = MooxPackageDiscovery::packageSlug($packageName);

            if (in_array($slug, $skipSlugs, true)) {
                continue;
            }

            $entry = $this->resolveEntrySeeder($packageName, $providerClass, $nestedBasenames);

            if ($entry === null) {
                continue;
            }

            $candidates[$slug] = [
                'package' => $packageName,
                'slug' => $slug,
                'class' => $entry['class'],
                'seeder' => $entry['name'],
            ];
        }

        $sortedSlugs = $this->topologicalSort(array_keys($candidates), $priority);

        $ordered = [];
        foreach ($sortedSlugs as $slug) {
            if (isset($candidates[$slug])) {
                $ordered[] = $candidates[$slug];
            }
        }

        return $ordered;
    }

    /**
     * @param  list<string>  $nestedBasenames
     * @return array{name: string, class: string|null}|null
     */
    private function resolveEntrySeeder(string $packageName, string $providerClass, array $nestedBasenames): ?array
    {
        $composer = MooxPackageDiscovery::readComposerJson($packageName);
        $seedPath = $composer['extra']['moox']['install']['seed'] ?? null;

        if (is_string($seedPath) && $seedPath !== '') {
            $basename = basename($seedPath, '.php');
            $class = MooxPackageDiscovery::resolveSeederClass($packageName, $basename);

            return [
                'name' => $basename,
                'class' => $class,
            ];
        }

        try {
            $instance = new $providerClass(app());
            $mooxInfo = $instance->mooxInfo();
            $seeders = $mooxInfo['seeders'] ?? [];
        } catch (\Throwable) {
            return null;
        }

        foreach ($seeders as $seederName) {
            if (in_array($seederName, $nestedBasenames, true)) {
                continue;
            }

            if (str_ends_with($seederName, 'DatabaseSeeder')) {
                continue;
            }

            $class = MooxPackageDiscovery::resolveSeederClass($packageName, $seederName);

            return [
                'name' => $seederName,
                'class' => $class,
            ];
        }

        return null;
    }

    /**
     * @param  list<string>  $slugs
     * @param  array<string, int>  $priority
     * @return list<string>
     */
    private function topologicalSort(array $slugs, array $priority): array
    {
        $graph = MooxPackageDiscovery::mooxDependencyGraph();
        $slugSet = array_flip($slugs);
        $inDegree = array_fill_keys($slugs, 0);
        $adjacency = array_fill_keys($slugs, []);

        foreach ($slugs as $slug) {
            foreach ($graph[$slug] ?? [] as $dep) {
                if (! isset($slugSet[$dep])) {
                    continue;
                }

                $adjacency[$dep][] = $slug;
                $inDegree[$slug]++;
            }
        }

        $queue = [];
        foreach ($slugs as $slug) {
            if ($inDegree[$slug] === 0) {
                $queue[] = $slug;
            }
        }

        usort($queue, fn (string $a, string $b): int => ($priority[$a] ?? 999) <=> ($priority[$b] ?? 999));

        $sorted = [];

        while ($queue !== []) {
            $current = array_shift($queue);
            $sorted[] = $current;

            foreach ($adjacency[$current] ?? [] as $neighbor) {
                $inDegree[$neighbor]--;
                if ($inDegree[$neighbor] === 0) {
                    $queue[] = $neighbor;
                }
            }

            usort($queue, fn (string $a, string $b): int => ($priority[$a] ?? 999) <=> ($priority[$b] ?? 999));
        }

        if (count($sorted) !== count($slugs)) {
            foreach ($slugs as $slug) {
                if (! in_array($slug, $sorted, true)) {
                    $sorted[] = $slug;
                }
            }
        }

        return $sorted;
    }
}
