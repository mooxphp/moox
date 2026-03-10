<?php

namespace Moox\Core\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class PackageDependencyGraph
{
    /**
     * Build a dependency graph for all local Moox packages in /packages.
     *
     * @return array{
     *     nodes: string[],
     *     edges: array<string, string[]>,
     *     root: string|null
     * }
     */
    public function buildGraph(): array
    {
        $packagesDir = base_path('packages');

        if (! File::isDirectory($packagesDir)) {
            return [
                'nodes' => [],
                'edges' => [],
                'root' => null,
            ];
        }

        $nodes = [];
        $edges = [];

        foreach (File::directories($packagesDir) as $dir) {
            $composerPath = $dir.'/composer.json';

            if (! File::isFile($composerPath)) {
                continue;
            }

            $data = json_decode(File::get($composerPath), true);

            if (! is_array($data)) {
                continue;
            }

            $packageName = Arr::get($data, 'name');

            if (! is_string($packageName) || ! str_starts_with($packageName, 'moox/')) {
                continue;
            }

            $nodes[] = $packageName;

            $requires = array_keys(Arr::get($data, 'require', []));

            $edges[$packageName] = array_values(array_filter(
                $requires,
                static fn (string $require) => str_starts_with($require, 'moox/')
            ));
        }

        $nodes = array_values(array_unique($nodes));

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'root' => $this->detectRootPackage($nodes, $edges),
        ];
    }

    /**
     * Return the packages in topological order (dependencies first).
     *
     * @return string[]
     */
    public function getTopologicallySortedPackages(): array
    {
        $graph = $this->buildGraph();

        return $this->topologicalSort($graph['nodes'], $graph['edges']);
    }

    /**
     * Return a structure that is easy to visualize as a graph/tree.
     *
     * @return array<string, array{
     *     depends_on: string[],
     *     used_by: string[]
     * }>
     */
    public function getGraphForVisualization(): array
    {
        $graph = $this->buildGraph();
        $nodes = $graph['nodes'];
        $edges = $graph['edges'];

        $result = [];

        foreach ($nodes as $node) {
            $result[$node] = [
                'depends_on' => $edges[$node] ?? [],
                'used_by' => [],
            ];
        }

        foreach ($edges as $from => $dependencies) {
            foreach ($dependencies as $to) {
                if (! isset($result[$to])) {
                    $result[$to] = [
                        'depends_on' => [],
                        'used_by' => [],
                    ];
                }

                $result[$to]['used_by'][] = $from;
            }
        }

        // Ensure unique and sorted lists for stable output
        foreach ($result as &$info) {
            $info['depends_on'] = array_values(array_unique($info['depends_on']));
            sort($info['depends_on']);

            $info['used_by'] = array_values(array_unique($info['used_by']));
            sort($info['used_by']);
        }

        return $result;
    }

    /**
     * Topologically sort packages so that dependencies come before dependents.
     *
     * @param  string[]  $nodes
     * @param  array<string, string[]>  $edges
     * @return string[]
     */
    protected function topologicalSort(array $nodes, array $edges): array
    {
        $visited = [];
        $result = [];

        $visit = function (string $node) use (&$visit, &$visited, &$result, $edges): void {
            if (($visited[$node] ?? null) === 'temp') {
                // Cycle detected – ignore to avoid infinite recursion.
                return;
            }

            if (($visited[$node] ?? null) === 'perm') {
                return;
            }

            $visited[$node] = 'temp';

            foreach ($edges[$node] ?? [] as $dep) {
                $visit($dep);
            }

            $visited[$node] = 'perm';
            $result[] = $node;
        };

        foreach ($nodes as $node) {
            $visit($node);
        }

        $result = array_values(array_unique($result));

        return $result;
    }

    /**
     * Try to detect a reasonable "root" package for visualization.
     */
    protected function detectRootPackage(array $nodes, array $edges): ?string
    {
        if (empty($nodes)) {
            return null;
        }

        $allDeps = [];

        foreach ($edges as $dependencies) {
            foreach ($dependencies as $dep) {
                $allDeps[$dep] = true;
            }
        }

        $candidates = array_values(array_filter(
            $nodes,
            static fn (string $node) => ! isset($allDeps[$node])
        ));

        sort($candidates);

        return $candidates[0] ?? null;
    }
}
