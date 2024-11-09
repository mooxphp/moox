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
        Schema::create('builder_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('namespace');
            $table->text('description');
            $table->string('author');
            $table->string('website');
            $table->string('email');
            $table->enum('status', ['development', 'installable', 'installed']);
            $table->json('publish_status')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void

    {
        Schema::dropIfExists('builder_packages');
    }
};
