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
        Schema::create('tag_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('tag_id');
            $table->string('locale')->index();
            $table->string('title');
            $table->string('slug');
            $table->string('permalink', 512);
            $table->text('description')->nullable();
            $table->text('content')->nullable();
            $table->string('translation_status')->nullable();

            $table->nullableMorphs('created_by');
            $table->nullableMorphs('updated_by');
            $table->nullableMorphs('deleted_by'); 

            $table->nullableMorphs('author');

            $table->unique(['tag_id', 'locale']);
            $table->unique(['slug', 'locale']);
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');

             // Schedule fields
            $table->timestamp('to_publish_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('to_unpublish_at')->nullable();
            $table->timestamp('unpublished_at')->nullable();

            // Actor fields
            $table->nullableMorphs('published_by');
            $table->nullableMorphs('unpublished_by');

            // Soft delete fields
            $table->softDeletes();
            $table->timestamp('restored_at')->nullable();
            $table->nullableMorphs('restored_by');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tag_translations');
    }
};
