<?php

declare(strict_types=1);

namespace Moox\Restore\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'moox-restore:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installs Moox Restore, publishes configuration, migrations and registers plugins.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->art();
        $this->welcome();
        $this->publishConfiguration();
        $this->publishMigrations();
        $this->runMigrations();
        $providerPath = app_path('Providers/Filament');
        $panelsToregister = $this->getPanelProviderPath();
        if ($panelsToregister != null) {
            if (is_array($panelsToregister)) {
                foreach ($panelsToregister as $panelprovider) {
                    $this->registerPlugins($providerPath.'/'.$panelprovider);
                }
            } else {
                $this->registerPlugins($panelsToregister);
            }
        } else {
            $this->registerPlugins($panelsToregister[0]);
        }
        $this->sayGoodbye();
    }

    public function art(): void
    {
        info('

        ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓ ▓▓▓▓▓▓▓▓▓▓▓       ▓▓▓▓▓▓▓▓▓▓▓▓           ▓▓▓▓▓▓▓▓▓▓▓▓   ▓▓▓▓▓▓▓        ▓▓▓▓▓▓▓
        ▓▓▒░░▒▓▓▒▒░░░░░░▒▒▓▓▓▒░░░░░░░▒▓▓   ▓▓▓▓▒░░░░░░░▒▓▓▓▓     ▓▓▓▓▓▒░░░░░░░▒▒▓▓▓▓▓▒▒▒▒▓▓      ▓▓▓▒▒▒▒▓▓
        ▓▒░░░░░░░░░░░░░░░░░░░░░░░░░░░░░▓▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▓ ▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▓░░░░░▒▓▓   ▓▓▒░░░░░▓▓
        ▓▒░░░░░░▒▓▓▓▓▒░░░░░░░▒▓▓▓▓░░░░░▒▓▓▓░░░░░▒▓▓▓▓▒░░░░░░░▓▓▓▓░░░░░░▒▓▓▓▓▓░░░░░░▒▓▓░░░░░▒▓▓▓▓▓░░░░░▒▓▓
        ▓▒░░░░▓▓▓▓  ▓▓░░░░░▓▓▓  ▓▓▓░░░░▒▓▓░░░░▒▓▓▓   ▓▓▓▓░░░░░▓░░░░░░▓▓▓▓   ▓▓▓▒░░░░▓▓▓▒░░░░░▓▓▓░░░░░▓▓▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓░░░░▒▓▓        ▓▓▓░░▒░░░░░▓▓▓        ▓▓░░░░▒▓▓▓▓░░░░░░░░░░░▓▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓░░░░▒▓          ▓▓▓░░░░░▒▓▓          ▓▓▒░░░░▓ ▓▓▓░░░░░░░░░▓▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓░░░░▒▓▓        ▓▓▒░░░░░▒░░▒▓▓        ▓▓░░░░▒▓▓▓▒░░░░░▒░░░░░▒▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓▓░░░░▒▓▓▓   ▓▓▓▒░░░░░▒▒░░░░░▒▓▓▓   ▓▓▓░░░░░▓▓▓░░░░░▒▓▓▓░░░░░▒▓▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓▓▓░░░░░░▒▒▓▓▒░░░░░░▒▓▓▓▓░░░░░░░▒▒▓▓▒░░░░░░▓▓▓░░░░░▒▓▓▓▓▓▒░░░░░▓▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▓ ▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▒░░░░░▓▓▓   ▓▓▒░░░░░▒▓
        ▓▓░░░▒▓▓    ▓▓▒░░░▒▓▓    ▓▓░░░░▓▓  ▓▓▓▓▒░░░░░░▒▒▓▓▓▓     ▓▓▓▓▓▒▒░░░░░▒▒▓▓▓▓▓░░░░▒▓▓      ▓▓▓░░░░▒▓
        ▓▓▓▓▓▓▓      ▓▓▓▓▓▓▓     ▓▓▓▓▓▓▓▓    ▓▓▓▓▓▓▓▓▓▓▓▓           ▓▓▓▓▓▓▓▓▓▓▓▓  ▓▓▓▓▓▓▓▓        ▓▓▓▓▓▓▓▓

        ');
    }

    public function welcome(): void
    {
        info('Welcome to Moox Restore Installer');
    }

    public function publishConfiguration(): void
    {
        if (confirm('Do you wish to publish the configuration?', true)) {
            if (! File::exists('config/restore.php')) {
                info('Publishing Restore Configuration...');
                $this->callSilent('vendor:publish', ['--tag' => 'restore-config']);

                return;
            }
            warning('The Restore config already exist. The config will not be published.');
        }
    }

    public function publishMigrations(): void
    {
        if (confirm('Do you wish to publish the migrations?', true)) {
            if (Schema::hasTable('restore_destinations')) {
                warning('The restore_destination table already exists. The migrations will not be published.');

                return;
            }
            info('Publishing RestorDestinations Migrations...');
            $this->callSilent('vendor:publish', ['--tag' => 'restore-migrations']);
        }
    }

    public function runMigrations(): void
    {
        if (confirm('Do you wish to run the migrations?', true)) {
            info('Running Restore Migrations...');
            $this->callSilent('migrate');
        }
    }

    public function registerPlugins(string $providerPath): void
    {
        if (File::exists($providerPath)) {
            $content = File::get($providerPath);

            $intend = '                ';

            $namespace = "\Moox\Restore";

            $pluginsToAdd = multiselect(
                label: 'These plugins will be installed:',
                options: ['RestoreDestinationPlugin', 'RestoreBackupPlugin'],
                default: ['RestoreDestinationPlugin', 'RestoreBackupPlugin'],
            );

            $function = '::make(),';

            $pattern = '/->plugins\(\[([\s\S]*?)\]\);/';
            $newPlugins = '';

            foreach ($pluginsToAdd as $plugin) {
                $searchPlugin = '/'.$plugin.'/';
                if (preg_match($searchPlugin, $content)) {
                    warning("$plugin already registered.");
                } else {
                    $newPlugins .= $intend.$namespace.'\\'.$plugin.$function."\n";
                }
            }

            if ($newPlugins) {
                if (preg_match($pattern, $content)) {
                    info('Plugins section found. Adding new plugins...');

                    $replacement = "->plugins([$1\n$newPlugins\n            ]);";
                    $newContent = preg_replace($pattern, $replacement, $content);
                } else {
                    info('Plugins section created. Adding new plugins...');

                    $pluginsSection = "            ->plugins([\n$newPlugins\n            ]);";
                    $placeholderPattern = '/(\->authMiddleware\(\[.*?\]\))\s*\;/s';
                    $replacement = "$1\n".$pluginsSection;
                    $newContent = preg_replace($placeholderPattern, $replacement, $content, 1);
                }

                File::put($providerPath, $newContent);
            }
        } else {
            alert('There are no new plugins detected.');
        }
    }

    public function getPanelProviderPath(): string|array
    {
        $providerPath = app_path('Providers/Filament');
        $providers = File::allFiles($providerPath);
        if (count($providers) > 1) {
            $providerNames = [];
            foreach ($providers as $provider) {
                $providerNames[] = $provider->getBasename();
            }
            $providerPath = multiselect(
                label: 'Which Panel should it be registered',
                options: [...$providerNames],
                default: [$providerNames[0]],
            );
        }
        if (count($providers) == 1) {
            $providerPath .= '/'.$providers[0]->getBasename();
        }

        return $providerPath;
    }

    public function sayGoodbye(): void
    {
        note('Moox Restore installed successfully. Enjoy!');
    }
}
