<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_payment_mean_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('static_payment_mean_id');
            $table->string('locale')->index();
            $table->string('common_name');
            $table->text('description')->nullable();
            $table->unique(['static_payment_mean_id', 'locale'], 'pay_mean_tr_locale_uq');
            $table->foreign('static_payment_mean_id', 'pay_mean_tr_fk')
                ->references('id')
                ->on('static_payment_means')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_payment_mean_translations');
    }
};
