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
        Schema::create('platforms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('domain')->unique();
            $table->string('thumbnail')->nullable();
            $table->string('api_token', 80)->unique()->nullable();
            $table->boolean('master')->nullable();
            $table->boolean('locked')->nullable();
            $table->string('lock_reason')->nullable();
            $table->boolean('show_in_menu')->nullable();
            $table->tinyInteger('order')->nullable();

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
