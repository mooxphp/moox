<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_manager', function (Blueprint $table) {
            $foreignColumn = $table->foreignId('job_queue_worker_id')->nullable();

            $foreignColumn
                ->references('id')
                ->on('job_queue_workers')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_manager', function (Blueprint $table) {
            $table->dropForeign(['job_queue_worker_id']);
        });
    }
};
