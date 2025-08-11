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
    $this->art();
    $this->info('✨ Welcome to the Moox installer');

    if (! $this->checkForFilament()) {
        $this->error('❌ Filament installation required or aborted. Aborting installation.');
        return;
    }

    $existingPanels = $this->getExistingPanelsWithLogin();
    if (count($existingPanels) > 0) {
        $this->info('ℹ️ Existing panels with login found. Skipping panel selection.');
    } else {
        $this->selectedPanels = $this->selectPanels();
        if (empty($this->selectedPanels)) {
            $this->warn('⚠️ No panel bundle selected. Skipping package installation.');
            return;
        }
        $this->installPackages($this->selectedPanels);
    }

    $this->checkOrCreateFilamentUser();

    // $this->info('⚙️ Running php artisan filament:upgrade ...');

    $this->info('✅ Moox installed successfully. Enjoy! 🎉');
}



protected function getExistingPanelsWithLogin(): array
{
    $panelFiles = $this->getPanelProviderFiles();
    $panelsWithLogin = $this->filterPanelsWithLogin($panelFiles);

    return $panelsWithLogin->map(fn($file) => $file->getRelativePathname())->toArray();
}


    

   
    protected function getMooxPackages(): array
    {
        return [
            'Moox Complete' => ['shop', 'press', 'devops', 'cms', 'empty'],
            'None'          => [],
        ];
    }
}
