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
            $table->string('name')->unique();
            $table->string('domain')->unique();
            $table->boolean('show_in_menu')->nullable();
            $table->tinyInteger('order')->nullable();
            $table->boolean('read_only')->nullable();
            $table->boolean('locked')->nullable();
            $table->boolean('master')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('api_token', 80)->unique()->nullable();

            $table->index('name');
            $table->index('domain');

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
