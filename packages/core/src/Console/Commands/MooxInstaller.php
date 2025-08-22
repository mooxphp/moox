<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Console\Command;
use Moox\Core\Console\Traits\Art;
use Moox\Core\Console\Traits\CheckForFilament;
use Moox\Core\Console\Traits\CheckForMooxPackages;
use Moox\Core\Console\Traits\CheckOrCreateFilamentUser;
use Moox\Core\Console\Traits\InstallPackage;
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
        InstallPackage,
        InstallPackages,
        SelectFilamentPanel;

    protected $signature = 'moox:install';
    protected $description = 'Install Moox Packages or generate Filament Panels.';
    protected array $selectedPanels = [];

    public function __construct(protected PackageService $packageService)
    {
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

        if (! $this->checkForFilament()) {
            $this->error('❌ Filament installation required or aborted.');
            return;
        }

        match ($choice) {
            'packages' => $this->runPackageInstallFlow(),
            'panels'   => $this->runPanelGenerationFlow(),
        };
    }

    protected function runPackageInstallFlow(): void
    {
        $categories = $this->getAllKnownMooxPackages();
        $installed = $this->getInstalledMooxPackages();

        $this->displayPackageStatus($categories, $installed);

        $notInstalled = collect($categories)->flatten()->diff($installed)->toArray();
        sort($notInstalled);
        if (empty($notInstalled)) {
            $this->info('🎉 All Moox Packages are already installed!');
            return;
        }

        $selection = multiselect(
            label: 'Which of the not yet installed packages do you want to install?',
            options: array_combine($notInstalled, $notInstalled),
            required: true
        );

        if (empty($selection)) {
            $this->warn('⚠️ No selection made. Aborting.');
            return;
        }

        $existingPanel = $this->getPanelFromBootstrapProviders();

        $selectedPanelKey = null;
        if ($existingPanel !== null) {
            $panelChoice = select(
                label: 'A panel is already registered. Where should we register the selected packages?',
                options: [
                    'existing' => "Use existing panel ({$existingPanel})",
                    'choose'   => 'Choose one of Moox panels',
                ]
            );

            if ($panelChoice === 'existing') {
                $selectedPanelKey = $existingPanel;
            }
        }

        if ($selectedPanelKey === null) {
            $panelOptions = array_keys($this->panelMap);
            $selectedPanelKey = select(
                label: 'Select a panel to register the packages into',
                options: $panelOptions
            );
        }

        $this->ensurePanelForKey($selectedPanelKey);

        $providerPath = $this->panelMap[$selectedPanelKey]['path'] . '/' . ucfirst($selectedPanelKey) . 'PanelProvider.php';

        $changedAny = false;
        foreach ($selection as $package) {
            $packageData = ['name' => $package, 'composer' => $package];
            $changed = $this->installPackage($packageData, [$providerPath]);
            $changedAny = $changedAny || $changed;

            $this->updatePanelPackageComposerJson($selectedPanelKey, [$package]);
        }

        if ($changedAny) {
            $this->info('⚙️ Finalizing (package discovery + Filament upgrade) ...');
            $this->callSilent('package:discover');
            $this->callSilent('filament:upgrade');
        }

        $this->info('🎉 Selected packages have been installed successfully!');
    }

    protected function displayPackageStatus(array $categories, array $installed): void
    {
        foreach ($categories as $category => $packages) {
            $this->info("📂 {$category}:");

            $installedList = array_values(array_intersect($packages, $installed));
            sort($installedList);

            $notInstalledList = array_values(array_diff($packages, $installed));
            sort($notInstalledList);

            if (!empty($installedList)) {
                $this->line("  ✅ Installed:");
                foreach ($installedList as $pkg) {
                    $this->line("    • {$pkg}");
                }
            }

            if (!empty($notInstalledList)) {
                $this->line("  ➕ Available:");
                foreach ($notInstalledList as $pkg) {
                    $this->line("    • {$pkg}");
                }
            }

            $this->newLine();
        }
    }

    protected function determinePanelForPackage(string $package): string
    {
        $existingPanels = $this->getExistingPanelsWithLogin();

        if (!empty($existingPanels)) {
            $panelChoice = select(
                label: "Möchtest du das Package '{$package}' in ein bestehendes Panel registrieren oder ein neues Panel erstellen?",
                options: [
                    'existing' => 'Existierendes Panel',
                    'new'      => 'Neues Panel erstellen',
                ]
            );

            if ($panelChoice === 'existing') {
                $panel = select(
                    label: 'Wähle das bestehende Panel:',
                    options: $existingPanels
                );
            } else {
                $panel = $this->createNewPanelProvider();
            }
        } else {
            $panel = $this->createNewPanelProvider();
        }

        return $panel;
    }

    protected function runPanelGenerationFlow(): void
    {
        $existingPanels = $this->getExistingPanelsWithLogin();

        if (empty($existingPanels)) {
            $this->selectedPanels = $this->selectPanels();
            if (!empty($this->selectedPanels)) {
                $changed = $this->installPackages($this->selectedPanels);
            } else {
                $this->warn('⚠️ No panel bundle selected. Skipping package installation.');
                return;
            }
        } else {
            $this->info('ℹ️ Existing panels with login found. Skipping panel selection.');
        }

        $this->checkOrCreateFilamentUser();
        if (isset($changed) && $changed) {
            $this->info('⚙️ Finalizing (package discovery + Filament upgrade) ...');
            $this->callSilent('package:discover');
            $this->callSilent('filament:upgrade');
        }
        $this->info('✅ Moox Panels installed successfully. Enjoy! 🎉');
    }

    protected function getMooxPackages(): array
    {
        return collect($this->getAllKnownMooxPackages())->flatten()->toArray();
    }

    protected function getExistingPanelsWithLogin(): array
    {
        return $this->filterPanelsWithLogin($this->getPanelProviderFiles())
            ->map(fn($file) => $file->getRelativePathname())
            ->toArray();
    }

    protected function getAllKnownMooxPackages(): array
    {
        return [
            'Core & System' => ['moox/core','moox/build','moox/skeleton','moox/packages'],
            'Development Tools' => ['moox/devops','moox/devtools','moox/devlink'],
            'Content & Media' => ['moox/content','moox/page','moox/news','moox/press','moox/press-trainings','moox/press-wiki','moox/media'],
            'User & Authentication' => ['moox/user','moox/user-device','moox/user-session','moox/login-link','moox/passkey','moox/security'],
            'E-Commerce & Shop' => ['moox/shop','moox/item','moox/category'],
            'Collaboration & Productivity' => ['moox/clipboard','moox/jobs','moox/trainings','moox/progress'],
            'Data & Utilities' => ['moox/data','moox/backup-server','moox/restore','moox/audit','moox/expiry','moox/draft','moox/slug','moox/tag'],
            'UI Components & Icons' => ['moox/components','moox/featherlight','moox/laravel-icons','moox/flag-icons-circle','moox/flag-icons-origin','moox/flag-icons-rect','moox/flag-icons-square'],
            'Localization & Communication' => ['moox/localization','moox/notifications'],
        ];
    }
}
