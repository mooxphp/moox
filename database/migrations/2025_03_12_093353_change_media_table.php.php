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
        if (! Schema::hasColumn('media', 'name')) {
            Schema::table('media', function (Blueprint $table) {
                $table->string('name')->nullable();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->text('internal_note')->nullable();
                $table->string('alt')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('media')) {
            Schema::table('media', function (Blueprint $table) {
                $table->string('name')->nullable();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->text('internal_note')->nullable();
                $table->string('alt')->nullable();
            });
        }
    }
};
