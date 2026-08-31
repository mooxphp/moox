<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_templates', function (Blueprint $table): void {
            $table->string('subject')->nullable()->after('brand_name');
        });
    }

    public function down(): void
    {
        Schema::table('mail_templates', function (Blueprint $table): void {
            $table->dropColumn('subject');
        });
    }
};
