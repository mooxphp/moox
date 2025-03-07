<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('api_connection_id')->constrained();
            $table->integer('endpoint_id')->nullable();
            $table->enum('trigger', ['CRON', 'USER', 'WEBHOOK', 'SYSTEM']);
            $table->json('request_data');
            $table->json('response_data')->nullable();
            $table->string('status_code', 255);
            $table->string('error_message', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
// $log = \Moox\Connect\Models\ApiLog::create([
//     'api_connection_id' => 1,
//     'endpoint_id' => null,
//     'trigger' => 'SYSTEM',
//     'request_data' => ['method' => 'GET', 'url' => '/api/test'],
//     'response_data' => ['status' => 'success', 'data' => []],
//     'status_code' => '200',
//     'error_message' => null
// ]);
