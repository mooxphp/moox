<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_link_processes', function (Blueprint $table) {
            if (! Schema::hasColumn('login_link_processes', 'context')) {
                $table->string('context')->default('auth')->index()->after('slug');
            }

            if (! Schema::hasColumn('login_link_processes', 'template_key')) {
                $table->string('template_key')->nullable()->index()->after('content');
            }

            if (! Schema::hasColumn('login_link_processes', 'invalidate_prior')) {
                $table->boolean('invalidate_prior')->default(true)->after('expiry_minutes');
            }
        });

        if (Schema::hasColumn('login_link_processes', 'template_key')) {
            $rows = DB::table('login_link_processes')
                ->where(function ($query): void {
                    $query->whereNull('template_key')->orWhere('template_key', '');
                })
                ->orderBy('id')
                ->get();

            foreach ($rows as $process) {
                $key = $process->slug === 'ack' ? 'ack' : 'login';

                DB::table('login_link_processes')
                    ->where('id', $process->id)
                    ->update([
                        'template_key' => $key,
                        'context' => $process->slug === 'ack' ? 'public' : 'auth',
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('login_link_processes', function (Blueprint $table) {
            if (Schema::hasColumn('login_link_processes', 'invalidate_prior')) {
                $table->dropColumn('invalidate_prior');
            }

            if (Schema::hasColumn('login_link_processes', 'template_key')) {
                $table->dropColumn('template_key');
            }

            if (Schema::hasColumn('login_link_processes', 'context')) {
                $table->dropColumn('context');
            }
        });
    }
};
