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
        Schema::create('command_executions', function (Blueprint $table) {
            $table->id();
            $table->string('flow_id')->unique();
            $table->string('command_name');
            $table->string('command_description')->nullable();
            $table->enum('status', ['cancelled', 'completed', 'failed'])->default('cancelled');
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_at_step')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failed_at_step')->nullable();
            $table->text('error_message')->nullable();
            $table->json('steps')->nullable();
            $table->json('step_outputs')->nullable();
            $table->json('context')->nullable();
            $table->nullableMorphs('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_executions');
    }
};
