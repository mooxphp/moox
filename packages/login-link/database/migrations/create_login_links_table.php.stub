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
        if (Schema::hasTable('login_links')) {
            return;
        }

        Schema::create('login_links', function (Blueprint $table) {
            $table->id();
            $table->string('panel_id')->nullable()->index();
            $table->string('process')->default('login')->index();
            $table->string('user_type')->nullable();
            $table->string('user_id')->nullable();
            $table->index(['user_type', 'user_id']);
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->index(['subject_type', 'subject_id']);
            $table->json('payload')->nullable();
            $table->string('email')->index();
            $table->dateTime('expires_at')->index();
            $table->dateTime('used_at')->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_links');
    }
};
