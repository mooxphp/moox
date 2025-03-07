<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_endpoints', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 255);
            $table->foreignId('api_connection_id')->constrained();
            $table->string('path', 255);
            $table->string('method', 255);
            $table->boolean('direct_access');
            $table->json('variables')->nullable();
            $table->json('response_map')->nullable();
            $table->json('expected_response');
            $table->json('field_mappings')->nullable();
            $table->json('transformers')->nullable();
            $table->string('lang_override', 255)->nullable();
            $table->integer('rate_limit')->nullable();
            $table->integer('rate_window')->nullable();
            $table->enum('status', ['new', 'unused', 'active', 'error', 'disabled']);
            $table->integer('timeout');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_endpoints');
    }
};
