<?php

namespace Moox\Sync\Commands;

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
    protected $signature = 'mooxsync:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installs Moox Sync, publishes configuration, migrations and registers plugins.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->art();
        $this->welcome();
        $this->publish_configuration();
        $this->publish_migrations();
        $this->run_migrations();
        $this->register_plugins();
        $this->finish();
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
        info('Welcome to Moox Sync Installer');
    }

    public function publish_configuration(): void
    {
        if (confirm('Do you wish to publish the configuration?', true)) {
            info('Publishing Sync Configuration...');
            $this->callSilent('vendor:publish', ['--tag' => 'sync-config']);
        }
    }

    public function publish_migrations(): void
    {
        if (Schema::hasTable('sync')) {
            warning('The sync table already exists. The migrations will not be published.');
        } elseif (confirm('Do you wish to publish the migrations?', true)) {
            info('Publishing Sync Migrations...');
            $this->callSilent('vendor:publish', ['--tag' => 'sync-migrations']);
        }
    }

    public function run_migrations(): void
    {
        if (confirm('Do you wish to run the migrations?', true)) {
            info('Running Sync Migrations...');
            $this->call('migrate');
        }
    }

    public function register_plugins(): void
    {
        note('Registering the Filament Resources...');

        $providerPath = app_path('Providers/Filament/AdminPanelProvider.php');

        if (File::exists($providerPath)) {

            $content = File::get($providerPath);

            $intend = '                ';

            $namespace = "\Moox\Sync";

            $pluginsToAdd = multiselect(
                label: 'These plugins will be installed:',
                options: ['SyncPlugin', 'PlatformPlugin'],
                default: ['SyncPlugin', 'PlatformPlugin'],
            );

            $function = '::make(),';

            $pattern = '/->plugins\(\[([\s\S]*?)\]\);/';
            $newPlugins = '';

            foreach ($pluginsToAdd as $plugin) {
                $searchPlugin = '/'.$plugin.'/';
                if (preg_match($searchPlugin, $content)) {
                    info("$plugin already registered.");
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

            alert('AdminPanelProvider not found. You need to add the plugins manually.');
        }
    }

    public function finish(): void
    {
        note('Moox Sync installed successfully. Enjoy!');
    }
}
