<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_unit_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('static_unit_id');
            $table->string('locale')->index();
            $table->string('common_name');
            $table->text('description')->nullable();
            $table->unique(['static_unit_id', 'locale'], 'unit_tr_locale_uq');
            $table->foreign('static_unit_id', 'unit_tr_fk')
                ->references('id')
                ->on('static_units')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_unit_translations');
    }
};
