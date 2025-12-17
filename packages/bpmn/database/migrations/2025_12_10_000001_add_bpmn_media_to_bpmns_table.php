<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bpmns', function (Blueprint $table) {
            $table->foreignId('bpmn_media')
                ->nullable()
                ->constrained('media')
                ->nullOnDelete()
                ->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('bpmns', function (Blueprint $table) {
            $table->dropForeign(['bpmn_media']);
            $table->dropColumn('bpmn_media');
        });
    }
};

