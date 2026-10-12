<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_testing_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mail_testing_run_id')->constrained('mail_testing_runs')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('html_hash', 40);
            $table->unsignedInteger('byte_length');
            $table->unsignedInteger('compose_ms');
            $table->unsignedInteger('convert_ms');
            $table->unsignedInteger('persist_ms');
            $table->string('storage_path')->nullable();
            $table->longText('html')->nullable();
            $table->timestamps();

            $table->unique(['mail_testing_run_id', 'position']);
            $table->index(['mail_testing_run_id', 'html_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_testing_messages');
    }
};
