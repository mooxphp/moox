<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_links', function (Blueprint $table) {
            if (! Schema::hasColumn('login_links', 'process')) {
                // First-class process discriminator (formerly the soft expiry_job concept).
                $table->string('process')->default('login')->index()->after('panel_id');
            }

            if (! Schema::hasColumn('login_links', 'subject_type')) {
                $table->nullableMorphs('subject');
            }
        });

        if (
            Schema::hasColumn('login_links', 'subject_type')
            && Schema::hasColumn('login_links', 'user_type')
        ) {
            DB::table('login_links')
                ->whereNull('subject_type')
                ->whereNotNull('user_type')
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->each(function (object $link): void {
                    DB::table('login_links')->where('id', $link->id)->update([
                        'subject_type' => $link->user_type,
                        'subject_id' => $link->user_id,
                        'process' => filled($link->process ?? null) ? $link->process : 'login',
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::table('login_links', function (Blueprint $table) {
            if (Schema::hasColumn('login_links', 'subject_type')) {
                $table->dropMorphs('subject');
            }

            if (Schema::hasColumn('login_links', 'process')) {
                $table->dropColumn('process');
            }
        });
    }
};
