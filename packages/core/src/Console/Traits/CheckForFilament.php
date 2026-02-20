<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

trait CheckForFilament
{
    protected string $providerPath = 'app/Providers/Filament/AdminPanelProvider.php';

    public function checkForFilament(bool $silent = false): bool
    {
        if (! class_exists(\Filament\PanelProvider::class, false)) {
            $panelProviderPath = base_path('vendor/filament/filament/src/PanelProvider.php');

            if (! file_exists($panelProviderPath)) {
                error('❌ Filament is not installed. Please run: composer require filament/filament');

                if (! confirm('📦 Do you want to install filament/filament now?', true)) {
                    info('⛔ Installation cancelled.');

                    return false;
                }

                if (! $silent) {
                    info('📦 Running: composer require filament/filament...');
                }
                exec('composer require filament/filament:* 2>&1', $output, $returnVar);
                foreach ($output as $line) {
                    if (! $silent) {
                        info('    '.$line);
                    }
                }

                if ($returnVar !== 0) {
                    error('❌ Composer installation of Filament failed. Please check your setup.');

                    return false;
                }

                if (! $silent) {
                    info('✅ filament/filament successfully installed.');
                }
            } else {
                if (! $silent) {
                    info('✅ Filament is already installed.');
                }
            }
        } else {
            if (! $silent) {
                info('✅ Filament is already installed.');
            }
        }

        // Only analyze in panel generation flow. The packages flow should stay clean.
        if (! $silent && method_exists($this, 'isPanelGenerationMode') && $this->isPanelGenerationMode()) {
            $this->analyzeFilamentEnvironment();
        }

        return true;
    }

    public function hasPanelsWithLogin(): bool
    {
        $panelFiles = $this->getPanelProviderFiles();
        $panelsWithLogin = $this->filterPanelsWithLogin($panelFiles);

        return $panelsWithLogin->isNotEmpty();
    }

    protected function analyzeFilamentEnvironment(): void
    {
        info('🔍 Checking existing Filament PanelProviders...');
        $panelFiles = $this->getPanelProviderFiles();

        if ($panelFiles->isEmpty()) {
            warning('⚠️ No PanelProvider files found in your project.');

            return;
        }

        $panelsWithLogin = $this->filterPanelsWithLogin($panelFiles);

        info('📦 Found panel providers:');
        foreach ($panelFiles as $path) {
            $hasLogin = $panelsWithLogin->contains($path);
            $status = $hasLogin ? '✅ login() set' : '⚠️ no login()';
            $relative = str_starts_with($path, base_path())
                ? ltrim(str_replace(base_path(), '', $path), '/\\')
                : $path;
            info("  • {$relative} {$status}");
        }
    }

    protected function getPanelProviderFiles(): Collection
    {
        // Prefer providers registered in bootstrap/providers.php for accuracy
        $classes = $this->getProviderClassesFromBootstrap();
        $mappings = $this->getComposerPsr4Mappings();

        $paths = collect();
        foreach ($classes as $class) {
            $path = $this->resolveClassToPath($class, $mappings);
            if ($path && File::exists($path)) {
                $paths->push($path);
            }
        }

        // Fallback: scan filesystem if none resolved (e.g. during early installs)
        if ($paths->isEmpty()) {
            $paths = collect(File::allFiles(base_path()))
                ->filter(fn ($file) => str_ends_with($file->getFilename(), 'PanelProvider.php'))
                ->map(fn ($file) => $file->getRealPath());
        }

        return $paths->values();
    }

    protected function filterPanelsWithLogin(Collection $panelFiles): Collection
    {
        return $panelFiles->filter(function ($path) {
            $contents = @file_get_contents($path);

            return $contents !== false && str_contains($contents, '->login(');
        })->values();
    }

    protected function getProviderClassesFromBootstrap(): array
    {
        $bootstrapProvidersPath = base_path('bootstrap/providers.php');
        if (! File::exists($bootstrapProvidersPath)) {
            return [];
        }

        $content = File::get($bootstrapProvidersPath);
        if (! preg_match_all('/([\\\\A-Za-z0-9_]+)::class/', $content, $matches)) {
            return [];
        }

        return $matches[1];
    }

    protected function getComposerPsr4Mappings(): array
    {
        $composerPath = base_path('composer.json');
        if (! File::exists($composerPath)) {
            return [];
        }
        $json = json_decode(File::get($composerPath), true);
        $psr4 = $json['autoload']['psr-4'] ?? [];

        return is_array($psr4) ? $psr4 : [];
    }

    protected function resolveClassToPath(string $class, array $psr4): ?string
    {
        foreach ($psr4 as $namespacePrefix => $dir) {
            if (str_starts_with($class, rtrim($namespacePrefix, '\\'))) {
                $relative = str_replace('\\', '/', substr($class, strlen($namespacePrefix)));
                $full = base_path(rtrim($dir, '/').'/'.$relative.'.php');
                if (File::exists($full)) {
                    return $full;
                }
                // Try case-variant for "Panels" directory
                $fullAlt = str_replace('/Panels/', '/panels/', $full);
                if (File::exists($fullAlt)) {
                    return $fullAlt;
                }
            }
        }

        return null;
    }
}
