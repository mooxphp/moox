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
        Schema::create('static_countries_static_currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('static_countries')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('static_currencies')->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->unique(['country_id', 'currency_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('static_countries_static_currencies');
    }
};
