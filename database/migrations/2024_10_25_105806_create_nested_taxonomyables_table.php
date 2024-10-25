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
        Schema::create('nested_taxonomyables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nested_taxonomy_id')->constrained()->onDelete('cascade');
            $table->morphs('nested_taxonomyable', 'nested_tax_morph_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nested_taxonomyables');
    }
};
