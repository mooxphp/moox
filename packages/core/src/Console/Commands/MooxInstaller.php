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
        $this->info('✨ Welcome to the Moox Installer!');

        $choice = select(
            label: 'What would you like to do?',
            options: [
                'packages' => '📦 Install Moox Packages',
                'panels'   => '🖼️ Generate Filament Panels',
            ]
        );

        if (! $this->checkForFilament()) {
            $this->error('❌ Filament installation is required or was aborted.');
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
            label: 'Which of the not yet installed packages would you like to install?',
            options: array_combine($notInstalled, $notInstalled),
            required: true
        );

        if (empty($selection)) {
            $this->warn('⚠️ No selection made. Aborting.');
            return;
        }

        $selectedPanelKey = $this->determinePanelForPackage(implode(', ', $selection));

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
            $this->info('⚙️ Finalizing (package discovery + Filament upgrade)...');
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
        $existingPanels = $this->getAllPanelsFromBootstrap(); // Array von Provider-Pfaden

        // Key => Label für die Auswahl
        $panelOptions = [];
        foreach ($existingPanels as $index => $path) {
            $key = $this->panelKeyFromPath($path);
            if (!$key) continue;
            $uniqueKey = $key . '_' . $index; // eindeutiger Key
            $panelOptions[$uniqueKey] = $key . ' (' . $path . ')';
        }

        $panelChoice = select(
            label: "Would you like to register the package '{$package}' into an existing panel or create a new panel?",
            options: [
                'existing' => 'Existing Panel',
                'new'      => 'Create New Panel',
            ]
        );

        if ($panelChoice === 'existing') {
            if (empty($panelOptions)) {
                $this->warn('⚠️ No existing panels found. Creating a new panel instead.');
                return $this->selectNewPanel([]);
            }

            $selectedUniqueKey = select(
                label: 'Select an existing panel:',
                options: $panelOptions
            );

            return explode('_', $selectedUniqueKey)[0];
        } else {
            $existingKeys = array_map(fn($path) => $this->panelKeyFromPath($path), $existingPanels);
            return $this->selectNewPanel($existingKeys);
        }
    }

    // ✅ Korrektur: sauber alle Keys aus panelMap zurückgeben
    protected function panelKeyFromPath(string $path): ?string
    {
        $filename = pathinfo($path, PATHINFO_FILENAME); // z.B. CmsPanelProvider
        $key = strtolower(str_replace('PanelProvider', '', $filename));

        return in_array($key, array_keys($this->panelMap)) ? $key : null;
    }

    protected function selectNewPanel(array $existingPanels): string
    {
        $allPanels = array_keys($this->panelMap);
        $availablePanels = array_diff($allPanels, $existingPanels);

        if (empty($availablePanels)) {
            $this->warn('⚠️ No new panels available, using default.');
            return reset($allPanels);
        }

        $panelOptions = array_combine($availablePanels, $availablePanels);

        return select(
            label: 'Select a new panel:',
            options: $panelOptions
        );
    }

    protected function getAllPanelsFromBootstrap(): array
    {
        $providers = $this->getPanelProviderFiles();
        $panels = [];

        foreach ($providers as $providerFile) {
            $panels[] = $providerFile->getRelativePathname();
        }

        return array_values($panels);
    }

    protected function runPanelGenerationFlow(): void
    {
        // Nur eigene Panels mit login() prüfen, nicht Filament Standardprovider
        $existingPanels = $this->getExistingPanelsWithLogin();

        if (!empty($existingPanels)) {
            $this->info('ℹ️ Existing panels with login detected. Panel creation is skipped.');
            return;
        }

        // Wenn keine Panels existieren, Auswahl anzeigen
        $this->selectedPanels = $this->selectPanels();
        if (!empty($this->selectedPanels)) {
            $changed = $this->installPackages($this->selectedPanels);
        } else {
            $this->warn('⚠️ No panel bundle selected. Skipping package installation.');
            return;
        }

        $this->checkOrCreateFilamentUser();

        if (isset($changed) && $changed) {
            $this->info('⚙️ Finalizing (package discovery + Filament upgrade)...');
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
