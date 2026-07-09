<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_eas_scheme_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('static_eas_scheme_id');
            $table->string('locale')->index();
            $table->string('common_name');
            $table->text('description')->nullable();
            $table->unique(['static_eas_scheme_id', 'locale'], 'eas_sch_tr_locale_uq');
            $table->foreign('static_eas_scheme_id', 'eas_sch_tr_fk')
                ->references('id')
                ->on('static_eas_schemes')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_eas_scheme_translations');
    }
};
