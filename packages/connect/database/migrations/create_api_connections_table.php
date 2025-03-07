<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_connections', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 255);
            $table->string('base_url', 255);
            $table->enum('api_type', ['REST', 'GraphQL']);
            $table->enum('auth_type', ['Bearer', 'Basic', 'OAuth']);
            $table->json('auth_credentials')->nullable();
            $table->json('headers')->nullable();
            $table->integer('rate_limit')->nullable();
            $table->string('lang_param', 255)->nullable();
            $table->string('default_locale', 255)->nullable();
            $table->enum('status', ['New', 'Unused', 'Active', 'Error', 'Disabled']);
            $table->enum('notify_on_failure', ['1', '']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_connections');
    }
};
