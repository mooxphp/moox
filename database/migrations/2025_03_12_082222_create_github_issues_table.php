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
        Schema::create('github_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_id');
            $table->string('issue_title');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void

    {
        Schema::dropIfExists('github_issues');
    }
};
