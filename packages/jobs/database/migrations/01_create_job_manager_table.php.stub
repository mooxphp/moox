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
        Schema::create('job_manager', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('job_id');
            $table->string('name')->nullable();
            $table->string('queue')->nullable();
            $table->string('connection')->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->boolean('failed')->nullable();
            $table->integer('attempt');
            $table->integer('progress')->nullable();
            $table->text('exception_message')->nullable();
            $table->string('status');

            $table->index('job_id');
            $table->index('queue');
            $table->index('status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_manager');
    }
};
