<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Console\Command;
use Moox\Core\Console\Traits\Art;
use Moox\Core\Console\Traits\CheckForFilament;
use Moox\Core\Console\Traits\CheckForMooxPackages;
use Moox\Core\Console\Traits\CheckOrCreateFilamentUser;
use Moox\Core\Console\Traits\InstallPackages;
use Moox\Core\Console\Traits\SelectFilamentPanel;
use Moox\Core\Services\PackageService;

use function Laravel\Prompts\select;
use function Laravel\Prompts\multiselect;

class MooxInstaller extends Command
{
    use Art,
        CheckForFilament,
        CheckForMooxPackages,
        CheckOrCreateFilamentUser,
        InstallPackages,
        SelectFilamentPanel;

    protected $signature = 'moox:install';

    protected $description = 'Install Moox Packages or generate Filament Panels.';

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

        $choice = select(
            label: 'What would you like to do?',
            options: [
                'packages' => '📦 Install Moox Packages',
                'panels'   => '🖼️ Generate Filament Panels',
            ]
        );

        if ($choice === 'packages') {
            $this->runPackageInstallFlow();
        } else {
            $this->runPanelGenerationFlow();
        }
    }

    protected function runPackageInstallFlow(): void
    {
        $categories = $this->getAllKnownMooxPackages();
        $installed = $this->getInstalledMooxPackages();

        $this->info("📚 All Moox Packages (installed or not):\n");

        foreach ($categories as $category => $packages) {
            $this->line("  🔹 {$category}");
            foreach ($packages as $pkg) {
                $status = in_array($pkg, $installed, true) ? '✅ installed' : '— not installed';
                $this->line("    • {$pkg} {$status}");
            }
            $this->newLine();
        }

        $notInstalled = collect($categories)->flatten()->diff($installed)->toArray();
        if (empty($notInstalled)) {
            $this->info('🎉 All Moox Packages are already installed!');
            return;
        }

        $options = array_combine($notInstalled, $notInstalled);

        $selection = multiselect(
            label: 'Which of the not yet installed packages do you want to install?',
            options: $options,
            required: false
        );

        if (empty($selection)) {
            $this->warn('⚠️ No selection made. Aborting.');
            return;
        }

        foreach ($selection as $package) {
            if (! $this->checkForMooxPackage($package)) {
                $this->error("❌ Installation of {$package} failed or was aborted.");
            }
        }

        $this->info('🎉 The selected packages have been installed (if possible).');
    }

    protected function runPanelGenerationFlow(): void
    {
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

        $this->info('⚙️ Running php artisan filament:upgrade ...');
        $this->info('✅ Moox Panels installed successfully. Enjoy! 🎉');
    }

    protected function getMooxPackages(): array
    {
        return collect($this->getAllKnownMooxPackages())->flatten()->toArray();
    }

    protected function getExistingPanelsWithLogin(): array
    {
        $panelFiles = $this->getPanelProviderFiles();
        $panelsWithLogin = $this->filterPanelsWithLogin($panelFiles);

        return $panelsWithLogin->map(fn($file) => $file->getRelativePathname())->toArray();
    }

    protected function getAllKnownMooxPackages(): array
    {
        return [
            'Core & System' => [
                'moox/core',
                'moox/build',
                'moox/skeleton',
                'moox/packages',
            ],
            'Development Tools' => [
                'moox/devops',
                'moox/devtools',
                'moox/devlink',
            ],
            'Content & Media' => [
                'moox/content',
                'moox/page',
                'moox/news',
                'moox/press',
                'moox/press-trainings',
                'moox/press-wiki',
                'moox/media',
            ],
            'User & Authentication' => [
                'moox/user',
                'moox/user-device',
                'moox/user-session',
                'moox/login-link',
                'moox/passkey',
                'moox/security',
            ],
            'E-Commerce & Shop' => [
                'moox/shop',
                'moox/item',
                'moox/category',
            ],
            'Collaboration & Productivity' => [
                'moox/clipboard',
                'moox/jobs',
                'moox/trainings',
                'moox/progress',
            ],
            'Data & Utilities' => [
                'moox/data',
                'moox/backup-server',
                'moox/restore',
                'moox/audit',
                'moox/expiry',
                'moox/draft',
                'moox/slug',
                'moox/tag',
            ],
            'UI Components & Icons' => [
                'moox/components',
                'moox/featherlight',
                'moox/laravel-icons',
                'moox/flag-icons-circle',
                'moox/flag-icons-origin',
                'moox/flag-icons-rect',
                'moox/flag-icons-square',
            ],
            'Localization & Communication' => [
                'moox/localization',
                'moox/notifications',
            ],
        ];
    }
}
