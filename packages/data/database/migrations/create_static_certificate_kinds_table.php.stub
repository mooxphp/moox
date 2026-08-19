<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_certificate_kinds', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->boolean('is_normative')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_certificate_kinds');
    }
};
