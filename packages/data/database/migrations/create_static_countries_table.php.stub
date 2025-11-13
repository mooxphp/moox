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
            $table->json('native_name')->nullable();
            $table->json('exonyms')->nullable();
            $table->string('region', 50)->nullable();
            $table->string('subregion', 100)->nullable();
            $table->smallInteger('calling_code')->nullable();
            $table->json('capital')->nullable();
            $table->bigInteger('population')->nullable();
            $table->decimal('area', 20, 2)->nullable();
            $table->json('links')->nullable();
            $table->json('tlds')->nullable();
            $table->json('membership')->nullable();
            $table->enum('embargo', ['none', 'partial', 'full'])->default('none');
            $table->json('embargo_data')->nullable();
            $table->json('address_format')->nullable();
            $table->string('postal_code_regex')->nullable();
            $table->string('dialing_prefix', 10)->nullable();
            $table->json('phone_number_formatting')->nullable();
            $table->string('date_format', 10)->default('YYYY-MM-DD');
            $table->json('currency_format')->nullable();
            $table->json('translations')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_countries');
    }
};
