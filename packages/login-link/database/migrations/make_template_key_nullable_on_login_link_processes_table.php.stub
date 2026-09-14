<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('login_link_processes') || ! Schema::hasColumn('login_link_processes', 'template_key')) {
            return;
        }

        Schema::table('login_link_processes', function (Blueprint $table) {
            $table->string('template_key')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('login_link_processes') || ! Schema::hasColumn('login_link_processes', 'template_key')) {
            return;
        }

        Schema::table('login_link_processes', function (Blueprint $table) {
            $table->string('template_key')->nullable(false)->change();
        });
    }
};
