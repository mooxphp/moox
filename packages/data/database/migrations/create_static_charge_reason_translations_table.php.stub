<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_charge_reason_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('static_charge_reason_id');
            $table->string('locale')->index();
            $table->string('common_name');
            $table->text('description')->nullable();
            $table->unique(['static_charge_reason_id', 'locale'], 'chg_rsn_tr_locale_uq');
            $table->foreign('static_charge_reason_id', 'chg_rsn_tr_fk')
                ->references('id')
                ->on('static_charge_reasons')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_charge_reason_translations');
    }
};
