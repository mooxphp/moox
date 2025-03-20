<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('static_locales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained('static_languages')->onDelete('cascade');
            $table->foreignId('country_id')->constrained('static_countries')->onDelete('cascade');
            $table->string('locale', 10);
            $table->string('name');
            $table->boolean('is_official_language')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('static_locales');
    }
};
