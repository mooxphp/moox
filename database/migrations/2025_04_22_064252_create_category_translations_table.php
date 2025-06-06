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
            $table->string('status')->default('draft');
            $table->string('slug');
            $table->text('content')->nullable();


            // Schedule fields
            $table->timestamp(column: 'to_publish_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('to_unpublish_at')->nullable();
            $table->timestamp('unpublished_at')->nullable();

            // Actor fields
            $table->nullableMorphs('published_by', 'cat_trans_pub_idx');
            $table->nullableMorphs('unpublished_by', 'cat_trans_unpub_idx');

            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();

            // Soft delete fields
            $table->softDeletes();
            $table->nullableMorphs('deleted_by');
            $table->timestamp('restored_at')->nullable();
            $table->nullableMorphs('restored_by');

            // Timestamps
            $table->timestamps();
            
            $table->unique(['category_id', 'locale']);
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
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
