<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('key');
            $table->string('locale', 8)->default('de');
            $table->string('view');
            $table->string('brand_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->longText('mail_content')->nullable();
            $table->longText('footer')->nullable();
            $table->timestamps();

            $table->unique(['key', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_templates');
    }
};
