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
        Schema::create('builder_entity_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('block_class');
            $table->json('options');
            $table->integer('sort_order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void

    {
        Schema::dropIfExists('builder_entity_blocks');
    }
};
