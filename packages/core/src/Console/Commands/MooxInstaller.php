<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Console\Command;
use Moox\Core\Console\Traits\Art;
use Moox\Core\Console\Traits\CheckForFilament;            // Schritt 1–2
use Moox\Core\Console\Traits\CheckOrCreateFilamentUser;   // Schritt 3
use Moox\Core\Console\Traits\InstallPackages;             // Schritt 5–7
use Moox\Core\Console\Traits\SelectFilamentPanel;         // Schritt 4
use Moox\Core\Services\PackageService;

class MooxInstaller extends Command
{
    use Art,
        CheckForFilament,
        CheckOrCreateFilamentUser,
        InstallPackages,
        SelectFilamentPanel;

    protected $signature = 'moox:install';

    protected $description = 'Install Moox Plugins and register all needed plugins.';

    protected array $selectedPanels = [];

    public function __construct(
        protected PackageService $packageService
    ) {
        parent::__construct();
        $this->setPackageService($packageService);
    }

    public function handle(): void
    {
        // 🖼️ Schritt 0: ASCII-Art Begrüßung
        $this->art();
        $this->info('✨ Welcome to the Moox installer');

        // ✅ Schritt 1: Filament prüfen/ggf. installieren
        // ✅ Schritt 2: PanelProvider-Dateien analysieren
        $this->checkForFilament();

        // ✅ Schritt 3: Admin-User prüfen/ggf. erstellen
        $this->checkOrCreateFilamentUser();

        // ✅ Schritt 4: Panel-Auswahl und automatische Erstellung inkl. auth(), plugin(), provider-Registrierung
        $this->selectedPanels = $this->selectPanels();

        // ⚠️ Falls keine Panel-Auswahl → Abbruch
        if (empty($this->selectedPanels)) {
            $this->warn('⚠️ No panel bundle selected. Skipping package installation.');
            return;
        }

        // ✅ Schritt 5–7:
        //   - composer require (falls Paket nicht installiert)
        //   - migrations / config / seeders
        //   - plugin registrieren
        //   - filament:upgrade ausführen
        $this->installPackages($this->selectedPanels);

        // ✅ Fertig
        $this->info('✅ Moox installed successfully. Enjoy! 🎉');
    }

    /**
     * Optional: Alternative Vorauswahl – wird aktuell nicht genutzt
     */
    protected function getMooxPackages(): array
    {
        return [
            'Moox Complete' => ['shop', 'press', 'devops', 'cms', 'empty'],
            'None'          => [],
        ];
    }
}
