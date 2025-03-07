<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('base_url');
            $table->enum('api_type', ['REST', 'GraphQL']);
            $table->enum('auth_type', ['bearer', 'basic', 'oauth', 'jwt']);
            $table->json('auth_credentials');
            $table->json('headers')->nullable();
            $table->integer('rate_limit')->nullable();
            $table->string('lang_param')->nullable();
            $table->string('default_locale')->nullable();
            $table->enum('status', ['new', 'unused', 'active', 'error', 'disabled'])->default('new');
            $table->boolean('notify_on_failure')->default(true);
            $table->string('notify_email')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['status', 'api_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_connections');
    }
};
