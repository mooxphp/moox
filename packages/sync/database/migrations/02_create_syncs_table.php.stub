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
            $table->unsignedBigInteger('syncable_id');
            $table->string('syncable_type');
            $table->unsignedBigInteger('source_platform_id');
            $table->unsignedBigInteger('target_platform_id');
            $table->timestamp('last_sync');

            $table->index('syncable_id');
            $table->index('syncable_type');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void

    {
        Schema::dropIfExists('sync');
    }
};
