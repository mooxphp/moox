<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_countries', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('alpha2', 255);
            $table->string('alpha3_b', 255)->nullable();
            $table->string('alpha3_t', 255)->nullable();
            $table->string('common_name', 255);
            $table->string('native_name', 255)->nullable();
            $table->json('exonyms')->nullable();
            $table->enum('region', ['Africa', 'Americas', 'Asia', 'Europe', 'Oceania', 'Antarctica'])->nullable();
            $table->enum('subregion', ['Northern Africa', 'Sub-Saharan Africa', 'Eastern Africa', 'Middle Africa', 'Southern Africa', 'Western Africa', 'Latin America and the Caribbean', 'Northern America', 'Caribbean', 'Central America', 'South America', 'Central Asia', 'Eastern Asia', 'South-Eastern Asia', 'Southern Asia', 'Western Asia', 'Eastern Europe', 'Northern Europe', 'Southern Europe', 'Western Europe', 'Australia and New Zealand', 'Melanesia', 'Micronesia', 'Polynesia'])->nullable();
            $table->string('calling_code', 255)->nullable();
            $table->string('capital', 255)->nullable();
            $table->string('population', 255)->nullable();
            $table->string('area', 255)->nullable();
            $table->json('links')->nullable();
            $table->json('tlds')->nullable();
            $table->json('membership')->nullable();
            $table->enum('embargo', ['New', 'Open', 'Pending', 'Closed'])->nullable();
            $table->text('embargo_data')->nullable();
            $table->text('address_format')->nullable();
            $table->string('postal_code_regex', 255)->nullable();
            $table->string('dialing_prefix', 255)->nullable();
            $table->text('phone_number_formatting')->nullable();
            $table->string('date_format', 10)->default('YYYY-MM-DD');
            $table->text('currency_format')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_countries');
    }
};
