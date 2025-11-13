<?php

namespace Moox\Core\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\info;

class PackageService
{
    /**
     * Return migration paths (relative to project base) declared by the package API.
     */
    public function getMigrations(array $package): array
    {
        $meta = $this->readPackageMeta($package['name'] ?? '');
        $auto = $meta['extra']['moox']['install']['auto_migrate'] ?? null;
        $paths = [];
        $allSkipped = true;
        $anyFound = false;

        foreach ($this->normalizeToArray($auto) as $rel) {
            $full = $this->toAbsolutePath($meta['baseDir'], $rel);
            if (! $full || ! File::isDirectory($full)) {
                continue;
            }

            foreach (File::files($full) as $file) {
                $anyFound = true;
                $filename = $file->getFilename();

                if (str_ends_with($filename, '.stub')) {
                    $baseName = str_replace('.php.stub', '.php', $filename);
                    $existing = collect(File::files(database_path('migrations')))
                        ->first(fn ($f) => str_ends_with($f->getFilename(), $baseName));

                    if ($existing) {
                        info("ℹ️ Migration '{$baseName}' already exists, skipped.");
                        $paths[] = 'database/migrations/'.$existing->getFilename();

                        continue;
                    }

                    $allSkipped = false;
                    $newName = date('Y_m_d_His').'_'.$baseName;
                    $target = database_path('migrations/'.$newName);

                    File::copy($file->getRealPath(), $target);
                    $content = File::get($target);
                    $content = str_replace(['{{ timestamp }}'], [date('Y_m_d_His')], $content);
                    File::put($target, $content);

                    $paths[] = 'database/migrations/'.$newName;
                    info("✅ Migration '{$baseName}' was created.");
                } elseif (str_ends_with($filename, '.php')) {
                    $paths[] = $file->getRealPath();
                    $allSkipped = false;
                }
            }
        }

        if (! $anyFound) {
            info('ℹ️ No migrations found.');

            return [];
        }

        if ($allSkipped) {
            info('ℹ️ All migrations already exist.');

            return [];
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
     * Publish package configs only if they do not exist yet.
     */
    public function publishConfigs(array $package): bool
    {
        $configs = $this->getConfig($package);
        $updatedAny = false;

        foreach ($configs as $path => $contentOrTrue) {
            if (is_string($path) && str_starts_with($path, 'tag:')) {
                $tag = substr($path, 4);
                $meta = $this->readPackageMeta($package['name'] ?? '');
                $packageConfigDir = rtrim($meta['baseDir'], '/').'/config';
                $filesInPackage = File::isDirectory($packageConfigDir) ? File::files($packageConfigDir) : [];

                $allExist = true;
                foreach ($filesInPackage as $file) {
                    $targetPath = config_path($file->getFilename());
                    if (! File::exists($targetPath)) {
                        $allExist = false;
                        break;
                    }
                }

                if ($allExist) {
                    info("ℹ️ Configs for package '{$package['name']}' already exist. Skipping tag '{$tag}'.");

                    continue;
                }

                info("📦 Publishing vendor tag: {$tag}");
                Artisan::call('vendor:publish', [
                    '--tag' => $tag,
                    '--force' => false,
                    '--no-interaction' => true,
                ]);
                $updatedAny = true;

                continue;
            }

            $publishPath = config_path(basename($path));
            if (! File::exists($publishPath)) {
                info("📄 Publishing new config: {$path}");
                File::put($publishPath, $contentOrTrue);
                $updatedAny = true;

                continue;
            }

            $existingContent = File::get($publishPath);
            if ($existingContent !== $contentOrTrue && confirm("⚠️ Config file {$path} has changes. Overwrite?", false)) {
                info("🔄 Updating config file: {$path}");
                File::put($publishPath, $contentOrTrue);
                $updatedAny = true;
            }
        }

        return $updatedAny;
    }

    public function getConfig(array $package): array
    {
        $meta = $this->readPackageMeta($package['name'] ?? '');
        $auto = $meta['extra']['moox']['install']['auto_publish'] ?? [];
        $result = [];

        foreach ($this->normalizeToArray($auto) as $tagOrPath) {
            if (is_string($tagOrPath) && $tagOrPath !== '') {
                $result['tag:'.$tagOrPath] = true;
            }
        }

        return $result;
    }

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
                $classes[] = $entry;
            }
        }

        return array_values(array_unique($classes));
    }

    public function getPlugins(array $package): array
    {
        $meta = $this->readPackageMeta($package['name'] ?? '');
        $baseDir = $meta['baseDir'];
        $psr4 = $meta['autoload']['psr-4'] ?? [];

        if (empty($psr4) || ! is_array($psr4)) {
            return [];
        }

        $baseNamespace = array_key_first($psr4);
        $nsPath = rtrim($psr4[$baseNamespace] ?? 'src', '/');
        $scanDir = rtrim($baseDir, '/').'/'.$nsPath;

        if (! File::isDirectory($scanDir)) {
            return [];
        }

        $plugins = [];
        $files = File::allFiles($scanDir);
        foreach ($files as $file) {
            $filename = $file->getFilename();
            if (! str_ends_with($filename, 'Plugin.php')) {
                continue;
            }

            $relativePath = str_replace($scanDir.'/', '', $file->getPathname());
            $relativeClass = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $fqcn = rtrim($baseNamespace, '\\').'\\'.$relativeClass;

            if (str_contains($fqcn, ' ')) {
                continue;
            }

            $plugins[] = $fqcn;
        }

        return array_values(array_unique($plugins));
    }

    public function getAutoRunCommands(array $package): array
    {
        $meta = $this->readPackageMeta($package['name'] ?? '');
        $cmds = $meta['extra']['moox']['install']['auto_run'] ?? [];

        return array_values(array_filter($this->normalizeToArray($cmds), fn ($c) => is_string($c) && $c !== ''));
    }

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

    private function normalizeToArray(mixed $value): array
    {
        if ($value === null || $value === false) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    private function readPackageMeta(string $composerName): array
    {
        $baseDir = $this->resolvePackageBaseDir($composerName);
        $composerJson = $baseDir ? $baseDir.'/composer.json' : null;
        $extra = [];
        $autoload = [];

        if ($composerJson && File::exists($composerJson)) {
            $json = json_decode(File::get($composerJson), true) ?: [];
            $extra = $json['extra'] ?? [];
            $autoload = $json['autoload'] ?? [];
        }

        return [
            'baseDir' => $baseDir ?: base_path(),
            'extra' => $extra,
            'autoload' => $autoload,
        ];
    }

    private function resolvePackageBaseDir(string $composerName): ?string
    {
        if ($composerName === '') {
            return null;
        }

        $short = str_contains($composerName, '/') ? explode('/', $composerName)[1] : $composerName;
        $local = base_path('packages/'.$short);
        if (File::isDirectory($local)) {
            return $local;
        }

        $vendor = base_path('vendor/'.$composerName);
        if (File::isDirectory($vendor)) {
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
        return rtrim($baseDir, '/').'/'.ltrim($relative, '/');
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
