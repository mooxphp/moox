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
        Schema::table('continents', function (Blueprint $table) {
            $table
                ->foreign('parent_continent_id')
                ->references('id')
                ->on('continents')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('continents', function (Blueprint $table) {
            $table->dropForeign(['parent_continent_id']);
        });
    }
};
