<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('slug');
            $table->unsignedBigInteger('continent_id');
            $table->boolean('delivery')->nullable();
            $table->string('official');
            $table->json('native_name');
            $table->string('tld')->nullable();
            $table->boolean('independent')->nullable();
            $table->boolean('un_member')->nullable();
            $table
                ->set('status', ['officially-assigned', 'user-assigned'])
                ->nullable();
            $table->string('cca2')->nullable();
            $table->string('ccn3')->nullable();
            $table->string('cca3')->nullable();
            $table->string('cioc')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
