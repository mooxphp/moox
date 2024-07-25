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
            $table->string('ip_address');
            $table->string('thumbnail')->nullable();
<<<<<<<< HEAD:database/migrations/2024_07_24_135754_01_create_platforms_table.php
            $table->string('api_token', 80)->unique()->nullable();
            $table->boolean('master')->nullable();
            $table->boolean('locked')->nullable();
            $table->string('lock_reason')->nullable();
            $table->boolean('show_in_menu')->nullable();
            $table->tinyInteger('order')->nullable();

========
            $table->string('platformable_type');
            $table->unsignedBigInteger('platformable_id');

            $table->index(['platformable_id', 'platformable_type']);
>>>>>>>> a748b85f (Sync wip):database/migrations/2024_07_22_104905_01_create_platforms_table.php
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
