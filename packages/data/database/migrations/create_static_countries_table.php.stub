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
            $table->string('alpha2', 2)->unique();
            $table->string('alpha3_b', 3)->nullable();
            $table->string('alpha3_t', 3)->nullable();
            $table->string('common_name');
            $table->text('native_name')->nullable();
            $table->json('exonyms')->nullable();
            $table->enum('region', ['Africa', 'Americas', 'Asia', 'Europe', 'Oceania', 'Antarctic'])->nullable();
            $table->enum('subregion', ['Northern Africa', 'Sub-Saharan Africa', 'Eastern Africa', 'Middle Africa', 'Southern Africa', 'Western Africa', 'Latin America and the Caribbean', 'North America', 'Caribbean', 'Central America', 'South America', 'Central Asia', 'Eastern Asia', 'South-Eastern Asia', 'Southern Asia', 'Western Asia', 'Central Europe', 'Eastern Europe', 'Northern Europe', 'Southern Europe', 'Western Europe', 'Australia and New Zealand', 'Melanesia', 'Micronesia', 'Polynesia'])->nullable();
            $table->smallInteger('calling_code')->nullable();
            $table->string('capital')->nullable();
            $table->bigInteger('population')->nullable();
            $table->float('area')->nullable();
            $table->json('links')->nullable();
            $table->json('tlds')->nullable();
            $table->json('membership')->nullable();
            $table->boolean('embargo', ['New', 'Open', 'Pending', 'Closed'])->nullable();
            $table->json('embargo_data')->nullable();
            $table->json('address_format')->nullable();
            $table->string('postal_code_regex')->nullable();
            $table->string('dialing_prefix', 10)->nullable();
            $table->json('phone_number_formatting')->nullable();
            $table->string('date_format', 10)->default('YYYY-MM-DD');
            $table->json('currency_format')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_countries');
    }
};
