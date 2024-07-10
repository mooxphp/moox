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
        Schema::table('country_currency', function (Blueprint $table) {
            $table
                ->foreign('country_id')
                ->references('id')
                ->on('countries')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table
                ->foreign('currency_id')
                ->references('id')
                ->on('currencies')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('country_currency', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['currency_id']);
        });
    }
};
