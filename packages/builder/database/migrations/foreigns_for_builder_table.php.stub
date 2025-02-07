<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('builder_entities', function (Blueprint $table) {
            $table->foreign('package_id')->references('id')->on('builder_packages')->nullOnDelete();
        });

        Schema::table('builder_entity_tabs', function (Blueprint $table) {
            $table->foreign('entity_id')->references('id')->on('builder_entities')->cascadeOnDelete();
        });

        Schema::table('builder_entity_blocks', function (Blueprint $table) {
            $table->foreign('entity_id')->references('id')->on('builder_entities')->cascadeOnDelete();
        });

        Schema::table('builder_entity_builds', function (Blueprint $table) {
            $table->foreign('entity_id')->references('id')->on('builder_entities')->cascadeOnDelete();
        });

        Schema::table('builder_package_versions', function (Blueprint $table) {
            $table->foreign('package_id')->references('id')->on('builder_packages')->cascadeOnDelete();
        });
    }
};
