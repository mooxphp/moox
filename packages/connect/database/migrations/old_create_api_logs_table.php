<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('endpoint_id')->nullable()->constrained('api_endpoints')->nullOnDelete();
            $table->enum('trigger', ['CRON', 'USER', 'WEBHOOK', 'SYSTEM']);
            $table->json('request_data');
            $table->json('response_data')->nullable();
            $table->string('status_code');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
