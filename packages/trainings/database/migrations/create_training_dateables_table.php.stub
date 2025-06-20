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
        Schema::create('training_dateables', function (Blueprint $table) {
            $table->unsignedBigInteger('training_date_id');
            $table->unsignedBigInteger('training_dateable_id');
            $table->string('training_dateable_type');

            $table->index('training_dateable_id');
            $table->index('training_dateable_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_dateables');
    }
};
