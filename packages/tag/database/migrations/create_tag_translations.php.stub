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
        Schema::create('tag_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('tag_id');
            $table->string('locale')->index();
            $table->string('title');
            $table->string('slug');
            $table->text('content')->nullable();

            // Ensure slug is unique per locale
            $table->unique(['slug', 'locale'], 'unique_slug_per_locale');
            
            // Ensure one translation per locale per tag
            $table->unique(['tag_id', 'locale'], 'unique_translation_per_locale');
            
            // Foreign key constraint
            $table->foreign('tag_id')
                ->references('id')
                ->on('tags')
                ->onDelete('cascade');
                
            // Add index for faster slug lookups
            $table->index(['slug'], 'tag_translations_slug_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tag_translations');
    }
};
