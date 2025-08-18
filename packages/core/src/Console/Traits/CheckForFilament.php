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

    /**
     * Prüft, ob Filament installiert ist und bietet ggf. Installation an
     *
     * @return bool true wenn Filament vorhanden (oder erfolgreich installiert), sonst false
     */
    public function checkForFilament(): bool
    {
        if (!class_exists(\Filament\PanelProvider::class, false)) {

            $panelProviderPath = base_path('vendor/filament/filament/src/PanelProvider.php');

            if (!file_exists($panelProviderPath)) {
                error('❌ Filament is not installed. Please run: composer require filament/filament');

                if (!confirm('📦 Do you want to install filament/filament now?', true)) {
                    info('⛔ Installation cancelled.');
                    return false;
                }

                info('📦 Running: composer require filament/filament...');
                exec('composer require filament/filament:* 2>&1', $output, $returnVar);
                foreach ($output as $line) {
                    info("    " . $line);
                }

                if ($returnVar !== 0) {
                    error('❌ Composer installation of Filament failed. Please check your setup.');
                    return false;
                }

                info('✅ filament/filament successfully installed.');
            } else {
                info('✅ Filament is already installed.');
            }
        } else {
            info('✅ Filament is already installed.');
        }

        $this->analyzeFilamentEnvironment();

        return true;
    }


    /**
     * Prüft, ob mindestens ein PanelProvider mit login() existiert.
     *
     * @return bool
     */
    public function hasPanelsWithLogin(): bool
    {
        $panelFiles = $this->getPanelProviderFiles();
        $panelsWithLogin = $this->filterPanelsWithLogin($panelFiles);

        return $panelsWithLogin->isNotEmpty();
    }

    /**
     * Analysiert vorhandene PanelProvider-Dateien.
     */
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
    }

    /**
     * Sucht rekursiv nach allen Dateien, die auf *PanelProvider.php enden.
     *
     * @return Collection<\SplFileInfo>
     */
    protected function getPanelProviderFiles(): Collection
    {
        return collect(File::allFiles(base_path()))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), 'PanelProvider.php'));
    }

    /**
     * Filtert PanelProvider-Dateien mit login().
     *
     * @param Collection<\SplFileInfo> $panelFiles
     * @return Collection<\SplFileInfo>
     */
    protected function filterPanelsWithLogin(Collection $panelFiles): Collection
    {
        return $panelFiles->filter(function ($file) {
            return str_contains(file_get_contents($file->getRealPath()), '->login(');
        });
    }
}
