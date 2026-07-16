<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_vat_category_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('static_vat_category_id');
            $table->string('locale')->index();
            $table->string('common_name');
            $table->text('description')->nullable();
            $table->unique(['static_vat_category_id', 'locale'], 'vat_cat_tr_locale_uq');
            $table->foreign('static_vat_category_id', 'vat_cat_tr_fk')
                ->references('id')
                ->on('static_vat_categories')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_vat_category_translations');
    }
};
