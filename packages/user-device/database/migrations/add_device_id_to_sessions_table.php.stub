<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sessions')) {
            return;
        }

        if (Schema::hasColumn('sessions', 'device_id')) {
            return;
        }

        Schema::table('sessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('device_id')->nullable()->index()->after('user_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sessions')) {
            return;
        }

        if (! Schema::hasColumn('sessions', 'device_id')) {
            return;
        }

        Schema::table('sessions', function (Blueprint $table): void {
            $table->dropColumn('device_id');
        });
    }
};
