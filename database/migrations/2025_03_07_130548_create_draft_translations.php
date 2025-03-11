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
        Schema::create('draft_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('draft_id');
            $table->string('locale')->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('content')->nullable();

            $table->unique(['draft_id', 'locale']);
            $table->foreign('draft_id')->references('id')->on('drafts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void

    {
        Schema::dropIfExists('draft_translations');
    }
};
