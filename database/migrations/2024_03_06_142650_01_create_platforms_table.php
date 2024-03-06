<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('platforms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('slug');
            $table->string('domain');
            $table->boolean('selection')->nullable();
            $table->tinyInteger('order')->nullable();
            $table->boolean('locked')->nullable();
            $table->boolean('master')->nullable();
            $table->string('thumbnail')->nullable();
            $table->unsignedBigInteger('platformable_id');
            $table->string('platformable_type');

            $table->index('platformable_id');
            $table->index('platformable_type');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platforms');
    }
};
