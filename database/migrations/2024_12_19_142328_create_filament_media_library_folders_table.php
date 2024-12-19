<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('filament_media_library_folders')) {
            Schema::create('filament_media_library_folders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->nullable();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('filament_media_library', 'folder_id')) {
            Schema::table('filament_media_library', function (Blueprint $table) {
                $table->foreignId('folder_id')->nullable()->after('alt_text')->constrained('filament_media_library_folders');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('filament_media_library', 'folder_id')) {
            Schema::table('filament_media_library', function (Blueprint $table) {
                $table->dropForeign(['folder_id']);
                $table->dropColumn('folder_id');
            });
        }

        Schema::dropIfExists('filament_media_library_folders');
    }
};
