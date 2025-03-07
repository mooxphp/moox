<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_languages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('alpha2', 255);
            $table->string('alpha3_b', 255)->nullable();
            $table->string('alpha3_t', 255)->nullable();
            $table->string('common_name', 255);
            $table->string('native_name', 255)->nullable();
            $table->enum('script', ['Latin', 'Han', 'Hangul', 'Cyrillic', 'Arabic', 'Devanagari', 'Other', 'Bengali', 'Gujarati', 'Kannada', 'Malayalam', 'Oriya', 'Punjabi', 'Tamil', 'Telugu']);
            $table->enum('direction', ['ltr', 'rtl']);
            $table->json('exonyms')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_languages');
    }
};
