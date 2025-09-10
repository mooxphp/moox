<?php

namespace Moox\Core\Services;

use Illuminate\Support\Facades\File;

class PackageService
{
    /**
     * Return migration paths (relative to project base) declared by the package API.
     * Supports extra.moox.install.auto_migrate as string or array.
     *
     * @param  array{name?:string}  $package
     * @return array<int,string>
     */
    public function getMigrations(array $package): array
    {
        $meta = $this->readPackageMeta($package['name'] ?? '');
        $auto = $meta['extra']['moox']['install']['auto_migrate'] ?? null;
        $paths = [];

        foreach ($this->normalizeToArray($auto) as $rel) {
            $full = $this->toProjectRelativePath($meta['baseDir'], $rel);
            if ($full) {
                $paths[] = $full;
            }
        }

        return $paths;
    }

    /**
     * Very light migration status; let Laravel handle idempotency.
     */
    public function checkMigrationStatus(string $migrationPath): array
    {
        return [
            'hasChanges' => true,
            'hasDataInDeletedFields' => false,
        ];
    }

    /**
     * Return config publish instructions. If the package exposes vendor publish tags
     * via extra.moox.install.auto_publish, encode as special keys 'tag:<name>' => true.
     *
     * @param  array{name?:string}  $package
     * @return array<string,string|true>
     */
    public function getConfig(array $package): array
    {
        $meta = $this->readPackageMeta($package['name'] ?? '');
        $auto = $meta['extra']['moox']['install']['auto_publish'] ?? [];
        $result = [];

        foreach ($this->normalizeToArray($auto) as $tagOrPath) {
            if (is_string($tagOrPath) && $tagOrPath !== '') {
                // Treat as vendor publish tag
                $result['tag:'.$tagOrPath] = true;
            }
        }

        return $result;
    }

    /**
     * Return seeder classes from extra.moox.install.seed.
     * Accepts class names or file paths; file paths will be parsed for FQCN.
     *
     * @param  array{name?:string}  $package
     * @return array<int,string> Fully-qualified class names
     */
    public function getRequiredSeeders(array $package): array
    {
        $meta = $this->readPackageMeta($package['name'] ?? '');
        $seed = $meta['extra']['moox']['install']['seed'] ?? [];
        $classes = [];

        foreach ($this->normalizeToArray($seed) as $entry) {
            if (! is_string($entry) || $entry === '') {
                continue;
            }

            if (str_ends_with($entry, '.php')) {
                $abs = $this->toAbsolutePath($meta['baseDir'], $entry);
                $fqcn = $this->extractClassFromFile($abs) ?? null;
                if ($fqcn) {
                    $classes[] = $fqcn;
                }
            } else {
                $classes[] = $entry; // assume already FQCN
            }
        }

        return array_values(array_unique($classes));
    }

    /**
     * Optional: plugins read from package API in future. Return empty for now.
     */
    public function getPlugins(array $package): array
    {
        return [];
    }

    /**
     * Return shell commands to run after install (project root).
     * Uses extra.moox.install.auto_run.
     *
     * @param  array{name?:string}  $package
     * @return array<int,string>
     */
    public function getAutoRunCommands(array $package): array
    {
        $meta = $this->readPackageMeta($package['name'] ?? '');
        $cmds = $meta['extra']['moox']['install']['auto_run'] ?? [];

        return array_values(array_filter($this->normalizeToArray($cmds), fn ($c) => is_string($c) && $c !== ''));
    }

    /**
     * Return shell commands to run after install (package dir).
     * Uses extra.moox.install.auto_runhere.
     *
     * @param  array{name?:string}  $package
     * @return array<int,array{cmd:string,cwd:string}>
     */
    public function getAutoRunHereCommands(array $package): array
    {
        $meta = $this->readPackageMeta($package['name'] ?? '');
        $baseDir = $meta['baseDir'];
        $cmds = $meta['extra']['moox']['install']['auto_runhere'] ?? [];
        $list = [];
        foreach ($this->normalizeToArray($cmds) as $cmd) {
            if (is_string($cmd) && $cmd !== '') {
                $list[] = ['cmd' => $cmd, 'cwd' => $baseDir];
            }
        }

        return $list;
    }

    /**
     * Helpers
     */
    private function normalizeToArray(mixed $value): array
    {
        if ($value === null || $value === false) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * @return array{baseDir:string,extra:array}
     */
    private function readPackageMeta(string $composerName): array
    {
        $baseDir = $this->resolvePackageBaseDir($composerName);
        $composerJson = $baseDir ? $baseDir.'/composer.json' : null;
        $extra = [];

        if ($composerJson && File::exists($composerJson)) {
            $json = json_decode(File::get($composerJson), true) ?: [];
            $extra = $json['extra'] ?? [];
        }

        return [
            'baseDir' => $baseDir ?: base_path(),
            'extra' => $extra,
        ];
    }

    private function resolvePackageBaseDir(string $composerName): ?string
    {
        if ($composerName === '') {
            return null;
        }

        // Prefer local path repo under packages/<name>
        $short = str_contains($composerName, '/') ? explode('/', $composerName)[1] : $composerName;
        $local = base_path('packages/'.$short);
        if (\Illuminate\Support\Facades\File::isDirectory($local)) {
            return $local;
        }

        // Fallback to vendor path
        $vendor = base_path('vendor/'.$composerName);
        if (\Illuminate\Support\Facades\File::isDirectory($vendor)) {
            return $vendor;
        }

        return null;
    }

    private function toProjectRelativePath(?string $baseDir, string $relative): ?string
    {
        if (! $baseDir) {
            return null;
        }
        $abs = $this->toAbsolutePath($baseDir, $relative);
        if (! $abs) {
            return null;
        }
        $base = rtrim(base_path(), '/');

        return ltrim(str_replace($base, '', $abs), '/');
    }

    private function toAbsolutePath(string $baseDir, string $relative): ?string
    {
        $path = rtrim($baseDir, '/').'/'.ltrim($relative, '/');

        return $path;
    }

    private function extractClassFromFile(string $filePath): ?string
    {
        if (! File::exists($filePath)) {
            return null;
        }
        $content = File::get($filePath);
        $namespace = null;
        $class = null;

        if (preg_match('/^\s*namespace\s+([^;]+);/m', $content, $m)) {
            $namespace = trim($m[1]);
        }
        if (preg_match('/^\s*class\s+([A-Za-z_][A-Za-z0-9_]*)/m', $content, $m)) {
            $class = trim($m[1]);
        }

        if ($class) {
            return $namespace ? ($namespace.'\\'.$class) : $class;
        }

        return null;
    }
}
