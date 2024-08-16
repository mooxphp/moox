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
        Schema::table('trainingables', function (Blueprint $table) {
            $table
                ->foreign('training_id')
                ->references('id')
                ->on('trainings')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainingables', function (Blueprint $table) {
            $table->dropForeign(['training_id']);
        });
    }
};
