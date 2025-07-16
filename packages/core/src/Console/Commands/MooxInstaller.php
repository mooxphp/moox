<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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

    protected array $availablePanels = ['empty', 'shop', 'press', 'devops', 'jobs'];

    public function __construct(
        protected PackageService $packageService
    ) {
        parent::__construct();
        $this->providerPath = app_path('Providers/Filament/AdminPanelProvider.php');
    }

    public function checkForFilamentUser(): bool
    {
        $userModel = config('filament.auth.providers.users.model') ?? \App\Models\User::class;

        if (!Schema::hasTable((new $userModel)->getTable())) {
            $this->warn("User table not found. Did you run migrations?");
            return false;
        }

        $userCount = $userModel::count();

        if ($userCount > 0) {
            $this->info("There are already {$userCount} Filament users.");
            return true;
        } else {
            $this->warn("No Filament users found.");
            return false;
        }
    }

    public function makeFilamentUser(): void
    {
        $userModel = config('filament.auth.providers.users.model') ?? \App\Models\User::class;

        $name = $this->ask('Enter a name for the admin user');
        $email = $this->ask('Enter an email for the admin user');
        $password = $this->secret('Enter a password for the admin user');

        $userModel::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("User {$email} created successfully.");
    }

    public function generateAdminPanel(): void
    {
        $panel = $this->choice('Which panel do you want to generate?', $this->availablePanels);

        $this->call('make:filament-panel', [
            'name' => ucfirst($panel),
        ]);

        $this->info("Filament panel '{$panel}' generated.");
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

        $this->checkOrCreateFilamentUser();

        $this->selectFilamentPanel();

        $this->generateAdminPanel();

        $this->installPackages();

        $this->info('Moox installed successfully. Enjoy!');
    }

}
