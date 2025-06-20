<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Moox\Core\Console\Traits\Art;
use Moox\Core\Console\Traits\CheckForFilament;
use Moox\Core\Console\Traits\InstallPackages;
use Moox\Core\Console\Traits\SelectFilamentPanel;
use Moox\Core\Services\PackageService;

class MooxInstaller extends Command
{
    use Art, CheckForFilament, InstallPackages, SelectFilamentPanel;

    protected $signature = 'moox:install';

    protected $description = 'Install Moox Plugins and register all needed plugins.';

    protected $providerPath;

    public function __construct(
        protected PackageService $packageService
    ) {
        parent::__construct();
        $this->providerPath = app_path('Providers/Filament/AdminPanelProvider.php');
    }

    public function getNavigationGroups(): array
    {
        return Config::get('core.navigation_groups');
    }

    public function getMooxPackages(): array
    {
        return $this->packageService->getInstalledMooxPackagesInfo();
    }

    public function handle(): void
    {
        $this->art();
        $this->info('Welcome to the Moox installer');
        $this->checkForFilament();
        $this->selectFilamentPanel();
        $this->installPackages();
        $this->info('Moox installed successfully. Enjoy!');
    }
}
