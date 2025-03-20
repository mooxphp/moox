<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            $table->nullableMorphs('model');
            $table->nullableMorphs('uploader');

            $table->uuid()->nullable()->unique();
            $table->nullableMorphs('original_model');
            $table->string('collection_name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->boolean('write_protected')->default(false);
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();

            $table->nullableTimestamps();
        });

        Schema::create('media_usables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->morphs('media_usable');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_usables');
        Schema::dropIfExists('media');
    }
};
