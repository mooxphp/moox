<?php

namespace Moox\Core\Console\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

trait CheckForFilament
{
    protected string $providerPath = 'app/Providers/Filament/AdminPanelProvider.php';

    public function checkForFilament(): void
    {
        if (! File::exists(base_path($this->providerPath))) {
            error('❌ The Filament AdminPanelProvider.php or FilamentServiceProvider.php file does not exist.');
            warning('⚠️  You should install FilamentPHP first, see https://filamentphp.com/docs/panels/installation');

            if (confirm('Do you want to install Filament now?', true)) {
                if (! array_key_exists('filament:install', Artisan::all())) {
                    error('❌ filament:install command not available. Please check if Filament is installed as a dependency.');
                    return;
                }

                info('▶️ Starting Filament installer...');
                $this->call('filament:install', ['--panels' => true]);
            }
        }

        if (
            ! File::exists(base_path($this->providerPath)) &&
            ! confirm('⚠️ Filament is not installed properly. Do you want to proceed anyway?', false)
        ) {
            info('⛔ Installation cancelled.');
            return;
        }

        $this->analyzeFilamentEnvironment();
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
        foreach ($panelFiles as $file) {
            $hasLogin = $panelsWithLogin->contains($file);
            $status = $hasLogin ? '✅ login() set' : '⚠️ no login()';
            info("  • {$file->getRelativePathname()} {$status}");
        }

        $this->showAvailableUserModels();
    }

    protected function getPanelProviderFiles(): Collection
    {
        return collect(File::allFiles(base_path()))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'PanelProvider.php'));
    }

    protected function filterPanelsWithLogin(Collection $panelFiles): Collection
    {
        return $panelFiles->filter(function ($file) {
            return str_contains(file_get_contents($file->getRealPath()), '->login(');
        });
    }

    protected function showAvailableUserModels(): void
    {
        $models = [
            'Moox\\User\\Models\\User',
            'Moox\\Press\\Models\\WpUser',
        ];

        $existing = array_filter($models, fn ($model) => class_exists($model));

        if (empty($existing)) {
            warning('⚠️ No usable user models found (e.g., Moox\\User\\Models\\User, Moox\\Press\\Models\\WpUser).');
        } else {
            info('👤 Available Moox user models:');
            foreach ($existing as $model) {
                info("  • {$model}");
            }
        }
    }
}
