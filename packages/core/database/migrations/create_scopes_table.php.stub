<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scopes', function (Blueprint $table): void {
            $table->id();

            $table->string('scope')->unique();
            $table->string('label')->nullable();

            $table->string('origin')->index();
            $table->string('source')->index();
            $table->string('context')->index();
            $table->string('boundary')->index();

            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scopes');
    }
};

