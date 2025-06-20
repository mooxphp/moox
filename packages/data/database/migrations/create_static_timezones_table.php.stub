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
        Schema::create('static_timezones', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('offset_standard', 6);
            $table->string('offset_dst', 6)->nullable();
            $table->boolean('dst')->default(false);
            $table->dateTime('dst_start')->nullable();
            $table->dateTime('dst_end')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('static_timezone');
    }
};
