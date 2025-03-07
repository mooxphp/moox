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
        Schema::create('restore_destinations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_id');
            $table->string('host');
            $table->json('env_data');
            $table->timestamps();

            $table->foreign('source_id')->references('id')->on('backup_server_sources')->onDelete('cascade');
        });
        Schema::create('restore_backups', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('in Progress');
            $table->longText('message')->nullable();
            $table->unsignedBigInteger('backup_id');
            $table->unsignedBigInteger('restore_destination_id');

            $table->timestamps();

            $table->foreign('backup_id')->references('id')->on('backup_server_backups')->onDelete('cascade');
            $table->foreign('restore_destination_id')->references('id')->on('restore_destinations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restore_destinations');
        Schema::dropIfExists('restore_backups');
    }
};
