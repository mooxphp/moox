<?php

namespace Moox\Jobs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

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
    protected $signature = 'mooxjobs:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Moox Jobs, publishes configuration, migrations and registers plugins.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->art();
        $this->welcome();
        $this->publish_configuration();
        $this->publish_migrations();
        $this->create_queue_tables();
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
        note('Welcome to the Moox Jobs installer');
    }

    public function publish_configuration(): void
    {
        if (confirm('Do you wish to publish the configuration?', true)) {
            info('Publishing Jobs Configuration...');
            $this->callSilent('vendor:publish', ['--tag' => 'jobs-config']);
        }
    }

    public function publish_migrations(): void
    {
        if (Schema::hasTable('job_manager')) {
            warning('The job monitor table already exists. The migrations will not be published.');
        } elseif (confirm('Do you wish to publish the migrations?', true)) {
            info('Publishing Jobs Migrations...');
            $this->callSilent('vendor:publish', ['--tag' => 'jobs-migrations']);
        }
    }

    public function create_queue_tables(): void
    {
        if ($createQueueTables = confirm('Do you wish to create the queue tables?', true)) {
            note('Your Jobs are using the database queue driver. Creating Queue Tables...');

            if (Schema::hasTable('jobs')) {
                note('The jobs table already exists.');
            } else {
                info('The jobs table will be created.');
                $this->callSilent('queue:table');
            }

            if (Schema::hasTable('failed_jobs')) {
                note('The failed jobs table already exists.');
            } else {
                info('The failed jobs table will be created.');
                $this->callSilent('queue:failed-table');
            }

            if (Schema::hasTable('job_batches')) {
                note('The jobs batches table already exists.');
            } else {
                info('The job batches table will be created.');
                $this->callSilent('queue:batches-table');
            }
        }
    }

    public function run_migrations(): void
    {
        if (confirm('Do you wish to run the migrations?', true)) {
            info('Running Jobs Migrations...');
            $this->call('migrate');
        }
    }

    public function register_plugins(): void
    {
        note('Registering the Filament Resources...');

        $queueDriver = '';

        if (config('queue.default') == 'database') {
            $queueDriver = 'database';
        }

        $providerPath = app_path('Providers/Filament/AdminPanelProvider.php');

        if (! File::exists($providerPath)) {

            info('The Filament AdminPanelProvider.php or FilamentServiceProvider.php file does not exist. We try to install now ...');

            $this->call('filament:install', ['--panels' => true]);

            info('Filament seems to be installed. Now proceeding with Moox Jobs installation ...');

        }

        if (File::exists($providerPath)) {

            $content = File::get($providerPath);

            $intend = '                ';

            $namespace = "\Moox\Jobs";

            if ($queueDriver != 'database') {
                warning('The queue driver is not set to database. Jobs waiting will not be installed.');

                $pluginsToAdd = multiselect(
                    label: 'These plugins will be installed:',
                    options: ['JobsPlugin', 'JobsWaitingPlugin', 'JobsFailedPlugin', 'JobsBatchesPlugin'],
                    default: ['JobsPlugin', 'JobsFailedPlugin', 'JobsBatchesPlugin'],
                );
            } else {
                $pluginsToAdd = multiselect(
                    label: 'These plugins will be installed:',
                    options: ['JobsPlugin', 'JobsWaitingPlugin', 'JobsFailedPlugin', 'JobsBatchesPlugin'],
                    default: ['JobsPlugin', 'JobsWaitingPlugin', 'JobsFailedPlugin', 'JobsBatchesPlugin'],
                );
            }

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
        }
    }

    public function finish(): void
    {
        note('Moox Jobs installed successfully. Enjoy!');
    }
}
