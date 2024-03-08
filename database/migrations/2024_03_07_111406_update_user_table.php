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
        Schema::table('users', function (Blueprint $table) {
            // name and email are part of Laravel's default
            if (! Schema::hasColumn('users', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'gender')) {
                $table->enum('gender', ['unknown', 'male', 'female', 'other'])->after('slug')->default('unknown');
            }
            if (! Schema::hasColumn('users', 'title')) {
                $table->string('title')->nullable()->after('gender');
            }
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('title');
            }
            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('users', 'website')) {
                $table->string('website')->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'description')) {
                $table->text('description')->nullable()->after('website');
            }
            // `email_verified_at`, `password`, and `remember_token` are part of Laravel's default
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            }
            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            }
            // Skipping `current_team_id` as it's from Jetstream
            if (! Schema::hasColumn('users', 'avatar_url')) {
                $table->string('avatar_url', 2048)->nullable()->after('two_factor_confirmed_at');
            }
            if (! Schema::hasColumn('users', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('avatar_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Sorry, it's the user table
    }
};
