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
        Schema::create('languages', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('slug');
            $table->string('isocode');
            $table->string('flag')->nullable();
            $table->boolean('active')->default(0);
            $table->boolean('published');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
