<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('moox_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('deployment_url');
            $table->integer('server_id');
            $table->integer('site_id');
            $table->string('site_link')->nullable();
            $table->string('admin_link')->nullable();
            $table->string('wp_link')->nullable();
            $table->string('horizon_link')->nullable();
            $table->string('mailcoach_link')->nullable();
            $table->string('flare_link')->nullable();
            $table->integer('repository_id')->nullable();
            $table->integer('failover_id')->nullable();
            $table->boolean('is_failover')->default(0);
            $table->integer('backup_id')->nullable();
            $table->timestamp('last_deployment')->nullable();
            $table->string('deployment_status')->nullable();
            $table->integer('deployed_by_user_id')->nullable();
            $table->boolean('lock_deployments')->nullable();
            $table->tinyInteger('commits_behind')->nullable();
            $table->string('last_commit_hash')->nullable();
            $table->string('last_commit_url')->nullable();
            $table->string('last_commit_message')->nullable();
            $table->string('last_commit_author')->nullable();
            $table->boolean('monitor_has_error')->nullable();
            $table->boolean('monitor_app')->nullable();
            $table->string('monitor_app_status')->nullable();
            $table->timestamp('monitor_app_latest')->nullable();
            $table->boolean('monitor_log')->nullable();
            $table->string('monitor_log_status')->nullable();
            $table->timestamp('monitor_log_latest')->nullable();
            $table->boolean('monitor_queue')->nullable();
            $table->string('monitor_queue_status')->nullable();
            $table->timestamp('monitor_queue_latest')->nullable();
            $table->boolean('monitor_schedule')->nullable();
            $table->string('monitor_schedule_status')->nullable();
            $table->timestamp('monitor_schedule_latest')->nullable();
            $table->boolean('monitor_workers')->nullable();
            $table->string('monitor_workers_status')->nullable();
            $table->timestamp('monitor_workers_latest')->nullable();
            $table->boolean('monitor_deployment')->nullable();
            $table->string('monitor_deployment_status')->nullable();
            $table->timestamp('monitor_deployment_latest')->nullable();
            $table->boolean('monitor_wphealth')->nullable();
            $table->string('monitor_wphealth_status')->nullable();
            $table->timestamp('monitor_wphealth_latest')->nullable();
            $table->boolean('monitor_wpupdates')->nullable();
            $table->string('monitor_wpupdates_status')->nullable();
            $table->timestamp('monitor_wpupdates_latest')->nullable();
            $table->boolean('monitor_wpbanners')->nullable();
            $table->string('monitor_wpbanners_status')->nullable();
            $table->timestamp('monitor_wpbanners_latest')->nullable();
            $table->boolean('monitor_ssl')->nullable();
            $table->string('monitor_ssl_status')->nullable();
            $table->timestamp('monitor_ssl_latest')->nullable();
            $table->boolean('monitor_dns')->nullable();
            $table->string('monitor_dns_status')->nullable();
            $table->timestamp('monitor_dns_latest')->nullable();
            $table->boolean('monitor_backup')->nullable();
            $table->string('monitor_backup_status')->nullable();
            $table->timestamp('monitor_backup_latest')->nullable();
            $table->boolean('monitor_failback')->nullable();
            $table->string('monitor_failback_status')->nullable();
            $table->timestamp('monitor_failback_latest')->nullable();
            $table->boolean('monitor_restore')->nullable();
            $table->string('monitor_restore_status')->nullable();
            $table->timestamp('monitor_restore_latest')->nullable();
            $table->boolean('monitor_failover')->nullable();
            $table->string('monitor_failover_status')->nullable();
            $table->timestamp('monitor_failover_latest')->nullable();
            $table->boolean('monitor_security')->nullable();
            $table->string('monitor_security_status')->nullable();
            $table->timestamp('monitor_security_latest')->nullable();
            $table->boolean('monitor_performance')->nullable();
            $table->string('monitor_performance_status')->nullable();
            $table->timestamp('monitor_performance_latest')->nullable();
            $table->boolean('monitor_audit')->nullable();
            $table->string('monitor_audit_status')->nullable();
            $table->timestamp('monitor_audit_latest')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moox_projects');
    }
};
