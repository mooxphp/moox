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
        Schema::table('training_dateables', function (Blueprint $table) {
            $table
                ->foreign('training_date_id')
                ->references('id')
                ->on('training_dates')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_dateables', function (Blueprint $table) {
            $table->dropForeign(['training_date_id']);
        });
    }
};
