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
        Schema::create('github_commits', function (Blueprint $table) {
            $table->id();
            $table->string('commit_hash');
            $table->string('commit_message');
            $table->string('commit_author');
            $table->string('commit_url');
            $table->timestamp('commit_timestamp');
            $table->integer('repository_id')->nullable();
            $table->integer('deployed_to_project_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void

    {
        Schema::dropIfExists('github_commits');
    }
};
