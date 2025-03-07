<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_connection_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('method');
            $table->boolean('direct_access')->default(false);
            $table->json('variables')->nullable();
            $table->json('response_map')->nullable();
            $table->json('expected_response');
            $table->json('field_mappings')->nullable();
            $table->json('transformers')->nullable();
            $table->string('lang_override')->nullable();
            $table->integer('rate_limit')->nullable();
            $table->integer('rate_window')->nullable();
            $table->enum('status', ['new', 'unused', 'active', 'error', 'disabled'])->default('new');
            $table->timestamp('last_used')->nullable();
            $table->timestamp('last_error')->nullable();
            $table->integer('error_count')->default(0);
            $table->integer('timeout')->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_endpoints');
    }
};
