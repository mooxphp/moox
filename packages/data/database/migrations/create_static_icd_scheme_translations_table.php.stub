<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_icd_scheme_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('static_icd_scheme_id');
            $table->string('locale')->index();
            $table->string('common_name');
            $table->text('description')->nullable();
            $table->unique(['static_icd_scheme_id', 'locale'], 'icd_sch_tr_locale_uq');
            $table->foreign('static_icd_scheme_id', 'icd_sch_tr_fk')
                ->references('id')
                ->on('static_icd_schemes')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_icd_scheme_translations');
    }
};
