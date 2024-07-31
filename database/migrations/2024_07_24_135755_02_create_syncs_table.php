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
            $table->boolean('status')->default(true);
            $table->string('title');
            $table->unsignedBigInteger('source_platform_id');
            $table->string('source_model');
            $table->unsignedBigInteger('target_platform_id');
            $table->string('target_model');
            $table->boolean('use_platform_relations')->default(false);
            $table->enum('if_exists', ['update', 'skip', 'error'])->default('update');
            $table->json('sync_ids')->nullable();
            $table->boolean('sync_all_fields')->default(true);
            $table->json('field_mappings')->nullable();
            $table->string('use_transformer_class')->nullable();
            $table->boolean('has_errors')->default(false);
            $table->string('error_message')->nullable();
            $table->integer('interval')->default(60);
            $table->timestamp('last_sync')->nullable();

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
