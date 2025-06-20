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
        Schema::create('training_invitationables', function (Blueprint $table) {
            $table->unsignedBigInteger('training_invitation_id');
            $table->unsignedBigInteger('training_invitationable_id');
            $table->string('training_invitationable_type');

            $table->index('training_invitationable_id');
            $table->index('training_invitationable_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_invitationables');
    }
};
