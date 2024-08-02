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
        Schema::create('training_dates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('training_invitation_id');
            $table->dateTime('begin');
            $table->dateTime('end');
            $table->set('type', ['onsite', 'teams', 'webex', 'slack', 'zoom']);
            $table->string('link')->nullable();
            $table->string('location')->nullable();
            $table->integer('min_participants')->nullable();
            $table->integer('max_participants')->nullable();
            $table->dateTime('sent_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_dates');
    }
};
