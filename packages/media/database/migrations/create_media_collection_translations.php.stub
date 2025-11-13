<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media_collection_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('media_collection_id');
            $table->string('locale')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['media_collection_id', 'locale']);
            $table->foreign('media_collection_id')->references('id')->on('media_collections')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_collection_translations');
    }
};
