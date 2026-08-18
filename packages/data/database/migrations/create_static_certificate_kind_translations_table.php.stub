<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_certificate_kind_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('static_certificate_kind_id');
            $table->string('locale')->index();
            $table->string('common_name');
            $table->text('description')->nullable();
            $table->unique(['static_certificate_kind_id', 'locale'], 'cert_kind_tr_locale_uq');
            $table->foreign('static_certificate_kind_id', 'cert_kind_tr_fk')
                ->references('id')
                ->on('static_certificate_kinds')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_certificate_kind_translations');
    }
};
