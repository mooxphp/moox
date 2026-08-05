<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_link_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('login_link_processes', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('login_link_processes', function (Blueprint $table) {
            if (Schema::hasColumn('login_link_processes', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
