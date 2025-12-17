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
        Schema::create('bpmns', function (Blueprint $table) {
            $table->id();
            $table->string('title', 60);
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(false);
            $table->string('status');
            $table->timestamps();
            $table->longText('bpmn_xml')->nullable();
            $table->longText('bpmn_svg')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bpmns');
    }
};
