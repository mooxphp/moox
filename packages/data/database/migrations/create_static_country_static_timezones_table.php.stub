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
        Schema::create('static_countries_static_timezones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('static_countries')->onDelete('cascade');
            $table->foreignId('timezone_id')->constrained('static_timezones')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('static_countries_static_timezones');
    }
};
