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
        Schema::create('category_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('category_id');
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

            $table->unique(['category_id', 'locale']);
            $table->unique(['slug', 'locale']);
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');

            // Schedule fields
            $table->timestamp('to_publish_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('to_unpublish_at')->nullable();
            $table->timestamp('unpublished_at')->nullable();
            

            // Actor fields
            $table->nullableMorphs('published_by', 'cat_trans_pub_idx');
            $table->nullableMorphs('unpublished_by', 'cat_trans_unpub_idx');


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
        Schema::dropIfExists('category_translations');
    }
};
