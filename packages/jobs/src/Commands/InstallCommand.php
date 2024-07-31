<?php

namespace Moox\Jobs\Commands;

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
    protected $signature = 'mooxjobs:install';

    protected $description = 'Install Moox Jobs, publishes configuration, migrations and registers plugins.';

    protected $providerPath;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->art();
        $this->welcome();
        $this->publishConfiguration();
        $this->publishMigrations();
        $this->createQueueTables();
        $this->runMigrations();
        $providerPath = app_path('Providers\Filament');
        $panelsToregister = $this->getPanelProviderPath();
        if (count($panelsToregister) > 0 && $panelsToregister != null) {
            foreach ($panelsToregister as $panelprovider) {
                $this->registerPlugins($providerPath.'/'.$panelprovider);
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
        note('Welcome to the Moox Jobs installer');
    }

    public function publishConfiguration(): void
    {
        if (confirm('Do you wish to publish the configuration?', true)) {
            if (! File::exists('config/jobs.php')) {
                info('Publishing Jobs Configuration...');
                $this->callSilent('vendor:publish', ['--tag' => 'jobs-config']);

                return;
            }
            warning('The Jobs config already exist. The config will not be published.');
        }
    }

    public function publishMigrations(): void
    {
        if (confirm('Do you wish to publish the migrations?', true)) {
            if (Schema::hasTable('job_batch_manager')) {
                warning('The job_batch_manager table already exists. The migrations will not be published.');
            } else {
                info('Publishing job_batch_manager Migrations...');
                $this->callSilent('vendor:publish', ['--tag' => 'jobs-batch-migration']);
            }
        }
        if (confirm('Do you wish to publish the migrations?', true)) {
            if (Schema::hasTable('job_queue_workers')) {
                warning('The job_queue_workers table already exists. The migrations will not be published.');
            } else {
                info('Publishing job_queue_workers Migrations...');
                $this->callSilent('vendor:publish', ['--tag' => 'jobs-queue-migration']);
            }
        }
        if (confirm('Do you wish to publish the migrations?', true)) {
            if (Schema::hasTable('job_manager')) {
                warning('The job_manager table already exists. The migrations will not be published.');
            } else {
                info('Publishing jobs_manager Migrations...');
                $this->callSilent('vendor:publish', ['--tag' => 'jobs-manager-migration']);
                info('Publishing job_manager foreigns Migrations...');
                $this->callSilent('vendor:publish', ['--tag' => 'jobs-manager-foreigns-migration']);
            }
        }
    }

    public function createQueueTables(): void
    {
        if (confirm('Do you wish to create the queue tables?', true)) {
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

    public function runMigrations(): void
    {
        if (confirm('Do you wish to run the migrations?', true)) {
            info('Running Jobs Migrations...');
            $this->callSilent('migrate');
        }
    }

    public function registerPlugins(string $providerPath): void
    {
        $queueDriver = '';

        if (config('queue.default') == 'database') {
            $queueDriver = 'database';
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
            } else {
                alert($providerPath.' not found. You need to add the plugins manually.');
            }
        }
    }

    public function getPanelProviderPath(): string|array
    {
        $providerPath = app_path('Providers\Filament');
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
        note('Moox Jobs installed successfully. Enjoy!');
    }
}
