<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Console\Command;
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

        $this->selectedPanels = $this->selectPanels();

        if (empty($this->selectedPanels)) {
            $this->info('No panel bundle selected. Skipping package installation.');
            return;
        }

        $this->installPackages($this->selectedPanels);

        $this->info('Moox installed successfully. Enjoy!');
    }

    protected function getMooxPackages(): array
    {
        return [
            'Moox Complete' => ['shop', 'press', 'devops', 'cms', 'empty'],
            'None'          => [],
        ];
    }
}
