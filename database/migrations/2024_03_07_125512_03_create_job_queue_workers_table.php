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
        Schema::create('job_queue_workers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('worker_pid');
            $table->string('queue');
            $table->string('connection');
            $table->string('worker_server')->nullable();
            $table->string('supervisor')->nullable();
            $table->string('status');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();

            $table->index('worker_pid');
            $table->index('queue');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_queue_workers');
    }
};
