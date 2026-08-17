<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_links', function (Blueprint $table) {
            if (Schema::hasColumn('login_links', 'panel_id')) {
                $table->string('panel_id')->nullable()->change();
            }

            if (! Schema::hasColumn('login_links', 'payload')) {
                $table->json('payload')->nullable()->after('subject_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('login_links', function (Blueprint $table) {
            if (Schema::hasColumn('login_links', 'payload')) {
                $table->dropColumn('payload');
            }
        });
    }
};
