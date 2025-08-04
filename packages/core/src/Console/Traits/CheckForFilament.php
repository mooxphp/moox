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
    // Standard-Pfad zum wichtigsten Filament PanelProvider
    protected string $providerPath = 'app/Providers/Filament/AdminPanelProvider.php';

    /**
     * Prüft, ob Filament installiert ist und bietet ggf. Installation an.
     */
    public function checkForFilament(): void
    {
        // Schritt 1: Composer require prüfen
        if (! class_exists(\Filament\PanelProvider::class)) {
            error('❌ Filament is not installed. Please run: composer require filament/filament');

            if (! confirm('📦 Do you want to install filament/filament now?', true)) {
                info('⛔ Installation cancelled.');
                return;
            }

            info('📦 Running: composer require filament/filament...');
            exec('composer require filament/filament:* 2>&1', $output, $returnVar);
            foreach ($output as $line) {
                line("    " . $line);
            }

            if ($returnVar !== 0) {
                error('❌ Composer installation of Filament failed. Please check your setup.');
                return;
            }

            info('✅ filament/filament successfully installed.');
        }

        // Schritt 2: Dateiprüfung
        if (! File::exists(base_path($this->providerPath))) {
            warning('⚠️ Filament panel file does not exist: ' . $this->providerPath);

            if (! confirm('Do you want to continue without a panel?', false)) {
                info('⛔ Installation cancelled.');
                return;
            }
        }

        // Schritt 3: Analyse vorhandener Panels
        $this->analyzeFilamentEnvironment();

        // Schritt 4: PanelProvider-Registrierung prüfen
        if (! $this->hasRegisteredPanelProvider()) {
            warning('⚠️ No PanelProvider registered in AppServiceProvider.');
        }

        // Schritt 5: Benutzer prüfen oder erstellen
        $this->checkOrCreateFilamentUser();
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

    /**
     * Prüft, ob ein PanelProvider in AppServiceProvider registriert ist.
     */
    protected function hasRegisteredPanelProvider(): bool
    {
        $appServiceProviderPath = app_path('Providers/AppServiceProvider.php');

        if (! File::exists($appServiceProviderPath)) {
            return false;
        }

        $content = File::get($appServiceProviderPath);

        return str_contains($content, 'PanelProvider::class');
    }
}
