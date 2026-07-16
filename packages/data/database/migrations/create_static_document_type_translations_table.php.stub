<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_document_type_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('static_document_type_id');
            $table->string('locale')->index();
            $table->string('common_name');
            $table->text('description')->nullable();
            $table->unique(['static_document_type_id', 'locale'], 'doc_type_tr_locale_uq');
            $table->foreign('static_document_type_id', 'doc_type_tr_fk')
                ->references('id')
                ->on('static_document_types')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_document_type_translations');
    }
};
