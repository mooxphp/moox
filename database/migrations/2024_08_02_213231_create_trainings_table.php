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
        Schema::create('trainings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->integer('duration');
            $table->string('link');
            $table->dateTime('due_at');
            $table
                ->set('cycle', [
                    'annually',
                    'half-yearly',
                    'quarterly',
                    'monthly',
                    'every 2 years',
                    'every 3 years',
                    'every 4 years',
                    'every 5 years',
                ])
                ->default('annually');
            $table->foreignId('source_id');
            $table->unsignedBigInteger('training_type_id');
            $table->unsignedBigInteger('trainingable_id');
            $table->string('trainingable_type');

            $table->index('trainingable_id');
            $table->index('trainingable_type');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
