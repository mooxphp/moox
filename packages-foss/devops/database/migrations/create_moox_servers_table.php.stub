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
        Schema::create('moox_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('forge_id')->nullable();
            $table->integer('envoyer_id')->nullable();
            $table->integer('vapor_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('type')->nullable();
            $table->string('provider')->nullable();
            $table->string('region')->nullable();
            $table->string('ubuntu_version')->nullable();
            $table->string('db_status')->nullable();
            $table->string('redis_status')->nullable();
            $table->string('php_version')->nullable();
            $table->boolean('is_ready')->nullable();
            $table->boolean('monitor_has_error')->nullable();
            $table->boolean('monitor_ping')->nullable();
            $table->string('monitor_ping_status')->nullable();
            $table->timestamp('monitor_ping_latest')->nullable();
            $table->boolean('monitor_http')->nullable();
            $table->string('monitor_http_status')->nullable();
            $table->timestamp('monitor_http_latest')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moox_servers');
    }
};
