<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_testing_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->string('engine');
            $table->string('persist_backend');
            $table->unsignedInteger('count');
            $table->unsignedInteger('processed')->default(0);
            $table->json('options');
            $table->string('options_fingerprint');
            $table->unsignedBigInteger('compose_ms')->default(0);
            $table->unsignedBigInteger('convert_ms')->default(0);
            $table->unsignedBigInteger('persist_ms')->default(0);
            $table->unsignedBigInteger('generation_ms')->default(0);
            $table->unsignedBigInteger('total_ms')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['engine', 'status', 'options_fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_testing_runs');
    }
};
