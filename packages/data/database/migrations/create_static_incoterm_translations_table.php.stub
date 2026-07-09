<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_incoterm_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('static_incoterm_id');
            $table->string('locale')->index();
            $table->string('common_name');
            $table->text('description')->nullable();
            $table->unique(['static_incoterm_id', 'locale'], 'incoterm_tr_locale_uq');
            $table->foreign('static_incoterm_id', 'incoterm_tr_fk')
                ->references('id')
                ->on('static_incoterms')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_incoterm_translations');
    }
};
