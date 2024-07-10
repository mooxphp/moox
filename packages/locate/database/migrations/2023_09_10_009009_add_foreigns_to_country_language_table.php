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
        Schema::table('country_language', function (Blueprint $table) {
            $table
                ->foreign('language_id')
                ->references('id')
                ->on('languages')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table
                ->foreign('country_id')
                ->references('id')
                ->on('countries')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('country_language', function (Blueprint $table) {
            $table->dropForeign(['language_id']);
            $table->dropForeign(['country_id']);
        });
    }
};
