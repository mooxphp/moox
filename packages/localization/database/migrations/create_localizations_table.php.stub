<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('localizations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('language_id')
                ->constrained('static_languages')
                ->cascadeOnDelete();

            $table->foreignId('fallback_language_id')
                ->nullable()
                ->constrained('localizations')
                ->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique;
            $table->boolean('is_active_admin')->default(false);
            $table->boolean('is_active_frontend')->default(false);
            $table->boolean('is_default')->default(false);

            $table->enum('fallback_behaviour', [
                'default',
                'link_to_fallback',
                'translate',
                'inform',
                'hide',
            ])->default('default');

            $table->enum('language_routing', [
                'path',
                'subdomain',
                'domain',
            ])->default('path');

            $table->string('routing_path')->nullable()->unique();
            $table->string('routing_subdomain')->nullable()->unique();
            $table->string('routing_domain')->nullable()->unique();

            $table->unsignedInteger('translation_status')->nullable();
            $table->json('language_settings')->nullable();

            $table->unique(['is_default', 'language_id'], 'unique_default_localization');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localizations');
    }
};
