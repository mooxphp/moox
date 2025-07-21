<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Moox\Core\Console\Traits\Art;
use Moox\Core\Console\Traits\CheckForFilament;
use Moox\Core\Console\Traits\CheckOrCreateFilamentUser;
use Moox\Core\Console\Traits\InstallPackages;
use Moox\Core\Console\Traits\SelectFilamentPanel;
use Moox\Core\Services\PackageService;

class MooxInstaller extends Command
{
    use Art, CheckForFilament, CheckOrCreateFilamentUser, InstallPackages, SelectFilamentPanel;

    protected $signature = 'moox:install';

    protected $description = 'Install Moox Plugins and register all needed plugins.';

    protected string $providerPath = 'app/Providers/Filament/AdminPanelProvider.php';

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
        $this->info('Welcome to the Moox installer');

        $this->checkForFilament();
        $this->checkOrCreateFilamentUser();

        $this->selectedPanels = $this->selectPanelBundle();

        if (empty($this->selectedPanels)) {
            $this->info('No panel bundle selected. Skipping package installation.');
            return;
        }

        $this->installPackages($this->selectedPanels);

        $this->createAdminPanelProvider();

        $this->info('Moox installed successfully. Enjoy!');
    }

    protected function createAdminPanelProvider()
    {
        $path = base_path($this->providerPath);

        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }

        if (!File::exists($path)) {
            $stubPath = base_path('app/stub/panels/admin-panel-provider.stub');
            $stub = File::get($stubPath);

            File::put($path, $stub);
            $this->info("Created AdminPanelProvider at {$this->providerPath}");
        } else {
            $this->info("AdminPanelProvider already exists at {$this->providerPath}");
        }
    }

    protected function getMooxPackages(): array
    {
        return [
            'Moox Complete' => ['admin', 'shop', 'press', 'devops', 'cms', 'empty'],
            'None'          => [],
        ];
    }
}
