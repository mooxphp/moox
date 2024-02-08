<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class UpdateJobManagerTable extends Command
{
    protected $signature = 'mooxjobs:update';

    protected $description = 'Update the job_manager table with new fields and indexes.';

    public function handle(): void
    {
        $this->art();
        $this->update_schema();
        $this->publish_migrations();
        $this->run_migrations();
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

    public function update_schema(): void
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
                    }
                    if (! Schema::hasColumn('job_manager', 'connection')) {
                        $table->string('connection');
                    }
                    if (! Schema::hasColumn('job_manager', 'job_queue_worker_id')) {
                        $table->unsignedBigInteger('job_queue_worker_id')->nullable();

                        $table->foreign('job_queue_worker_id')->references('id')->on('job_queue_workers')->onDelete('set null');
                    }

                    $table->index(['job_id'], 'job_manager_job_id_index');
                    $table->index(['queue'], 'job_manager_queue_index');
                    $table->index(['status'], 'job_manager_status_index');
                });

                info('job_manager table updated successfully.');
            } else {
                warning('The job_manager table does not exist. Let\'s publish the migration for it.');
                $this->callSilent('vendor:publish', ['--tag' => 'jobs-manager-migration']);
            }

        }
    }

    public function publish_migrations(): void
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

    public function run_migrations(): void
    {
        if (confirm('Do you wish to run the migrations?', true)) {
            info('Running Jobs Migrations...');
            $this->call('migrate');
        }
    }
}
