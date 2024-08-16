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
        Schema::table('training_dates', function (Blueprint $table) {
            $table
                ->foreign('training_invitation_id')
                ->references('id')
                ->on('training_invitations')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_dates', function (Blueprint $table) {
            $table->dropForeign(['training_invitation_id']);
        });
    }
};
