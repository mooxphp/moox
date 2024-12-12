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
        Schema::create('builder_entity_builds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id');
            $table->string('package_version')->nullable();
            $table->string('build_context');
            $table->json('data');
            $table->json('files');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void

    {
        Schema::dropIfExists('builder_entity_builds');
    }
};
