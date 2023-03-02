<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('icons', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('icon_set_id');
            $table->string('name');
            $table->json('keywords');
            $table->boolean('outlined');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */

    public function down(): void
    {
        Schema::dropIfExists('icons');
    }
};
