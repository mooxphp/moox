<?php

namespace Moox\Jobs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class InstallCommand extends Command
{
    protected $signature = 'mooxjobs:install';

    protected $description = 'Install Moox Jobs, publishes configuration, migrations and registers plugins.';

    protected $providerPath;

    public function __construct()
    {
        parent::__construct();
        $this->providerPath = app_path('Providers/Filament/AdminPanelProvider.php');
    }

    public function handle(): void
    {
        $this->art();
        $this->welcome();
        $this->check_for_filament();
        $this->publish_configuration();
        $this->publish_migrations();
        $this->create_queue_tables();
        $this->run_migrations();
        $this->register_plugins();
        $this->say_goodbye();
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

    public function check_for_filament(): void
    {

        if (! File::exists($this->providerPath)) {

            error('The Filament AdminPanelProvider.php or FilamentServiceProvider.php file does not exist.');
            info(' ');
            warning('You should install FilamentPHP first, see https://filamentphp.com/docs/panels/installation');
            info(' ');
            warning('You may proceed installing Moox Jobs anyway, but things might not work!');
            info(' ');
            if (! $this->confirm('Do you want to proceed anyway?', false)) {
                $this->info('Installation cancelled.');

                return; // cancel installation
            }
        }
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
        if (Schema::hasTable('job_batch_manager')) {
            warning('The job_batch_manager table already exists. The migrations will not be published.');
        } elseif (confirm('Do you wish to publish the migrations?', true)) {
            info('Publishing job_batch_manager Migrations...');
            $this->callSilent('vendor:publish', ['--tag' => 'jobs-batch-migration']);
        }

        if (Schema::hasTable('job_queue_workers')) {
            warning('The job_queue_workers table already exists. The migrations will not be published.');
        } elseif (confirm('Do you wish to publish the migrations?', true)) {
            info('Publishing job_queue_workers Migrations...');
            $this->callSilent('vendor:publish', ['--tag' => 'jobs-queue-migration']);
        }

        if (Schema::hasTable('job_manager')) {
            warning('The jobs-manager-migration table already exists. The migrations will not be published.');
        } elseif (confirm('Do you wish to publish the migrations?', true)) {
            info('Publishing jobs-manager-migration...');
            $this->callSilent('vendor:publish', ['--tag' => 'jobs-manager-migration']);

            info('Publishing job_manager foreigns Migrations...');
            $this->callSilent('vendor:publish', ['--tag' => 'jobs-manager-foreigns-migration']);
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

        if (! File::exists($this->providerPath)) {

            info('The Filament AdminPanelProvider.php or FilamentServiceProvider.php file does not exist. We try to install now ...');

            $this->call('filament:install', ['--panels' => true]);

            info('Filament seems to be installed. Now proceeding with Moox Jobs installation ...');

        }

        if (File::exists($this->providerPath)) {

            $content = File::get($this->providerPath);

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

                File::put($this->providerPath, $newContent);
            }
        }
    }

    public function say_goodbye(): void
    {
        note('Moox Jobs installed successfully. Enjoy!');
    }
}
