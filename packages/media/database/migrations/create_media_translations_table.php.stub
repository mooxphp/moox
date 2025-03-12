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
        Schema::create('media_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('media_id');
            $table->string('locale')->index();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('alt')->nullable();
            $table->text('description')->nullable();
            $table->text('internal_note')->nullable();
            $table->timestamps();

            $table->unique(['media_id', 'locale']);
            $table->foreign('media_id')->references('id')->on('media')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_translations');
    }
};
