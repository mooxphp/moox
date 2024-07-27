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
        Schema::create('syncs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title')->unique();
            $table->string('model');
            $table->unsignedBigInteger('source_platform_id');
            $table->unsignedBigInteger('target_platform_id');
            $table->timestamp('last_sync')->nullable();
            $table->boolean('has_errors')->default(false);
            $table->json('field_mappings');

            $table->foreign('source_platform_id')->references('id')->on('platforms')->onDelete('RESTRICT');
            $table->foreign('target_platform_id')->references('id')->on('platforms')->onDelete('RESTRICT');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('syncs', function (Blueprint $table) {
            $table->dropForeign(['source_platform_id']);
            $table->dropForeign(['target_platform_id']);
        });
        Schema::dropIfExists('syncs');
    }
};
