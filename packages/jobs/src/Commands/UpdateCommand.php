<?php

namespace Moox\Jobs\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class UpdateCommand extends Command
{
    protected $signature = 'mooxjobs:update';

    protected $description = 'Update the job_manager table with new fields and indexes.';

    public function handle(): void
    {
        $this->art();
        $this->updateSchema();
        $this->publishMigrations();
        $this->runMigrations();
        $this->migrateData();
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
        note('Welcome to the Moox Jobs updater V2 to V3');
    }

    public function updateSchema(): void
    {
        if (confirm('We make necessary updates to the database schema, OK?', true)) {
            info('Updating job_manager table...');

            if (Schema::hasTable('job_manager')) {
                Schema::table('job_manager', function (Blueprint $table) {
                    if (! Schema::hasColumn('job_manager', 'available_at')) {
                        $table->timestamp('available_at')->nullable();
                    }
                    if (! Schema::hasColumn('job_manager', 'status')) {
                        $table->string('status');
                        $table->index(['status'], 'job_manager_status_index');
                    }
                    if (! Schema::hasColumn('job_manager', 'connection')) {
                        $table->string('connection')->nullable();
                    }
                    if (! Schema::hasColumn('job_manager', 'job_queue_worker_id')) {
                        $table->unsignedBigInteger('job_queue_worker_id')->nullable();
                    }
                    if (Schema::hasColumn('job_manager', 'job_id')) {
                        $table->index(['job_id'], 'job_manager_job_id_index');
                    }
                    if (Schema::hasColumn('job_manager', 'queue')) {
                        $table->index(['queue'], 'job_manager_queue_index');
                    }
                });

                info('job_manager table updated successfully.');
                return;
            }
            warning('The job_manager table does not exist. Let\'s publish the migration for it.');
            $this->callSilent('vendor:publish', ['--tag' => 'jobs-manager-migration']);
        }
    }

    public function publishMigrations(): void
    {
        if (confirm('We publish the new table migrations, OK?', true)) {
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

            info('Publishing job_manager foreigns Migrations...');
            $this->callSilent('vendor:publish', ['--tag' => 'jobs-manager-foreigns-migration']);
        }
    }

    public function runMigrations(): void
    {
        if (confirm('Do you wish to run the migrations?', true)) {
            info('Running Jobs Migrations...');
            $this->call('migrate');
        }
    }

    public function migrateData(): void
    {
        $jobCount = DB::table('job_manager')->count();

        if ($jobCount > 0) {
            info("There are {$jobCount} entries in your job_manager table.");
            if (confirm('Do you want to migrate the jobs to show a correct status?')) {
                $jobs = DB::table('job_manager')->get();

                foreach ($jobs as $job) {
                    $status = 'Migrated';

                    if ($job->finished_at) {
                        $status = $job->failed ? 'Failed' : 'Completed';
                    } elseif ($job->started_at && is_null($job->finished_at)) {
                        $status = 'Running';
                    }

                    DB::table('job_manager')
                        ->where('id', $job->id)
                        ->update(['status' => $status]);
                }

                info('Existing job_manager data migration completed.');
            }
        }
    }

    public function sayGoodbye(): void
    {
        info('Moox Jobs is updated. Enjoy!');
    }
}
