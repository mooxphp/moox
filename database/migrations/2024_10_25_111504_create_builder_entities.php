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
        Schema::create('builder_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->nullable();
            $table->string('singular');
            $table->string('plural');
            $table->text('description')->nullable();
            $table->string('preset');
            $table->json('relations')->nullable();
            $table->json('taxonomies')->nullable();
            $table->string('build_context')->nullable();
            $table->timestamp('last_built_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('builder_entities');
    }
};
