<?php

declare(strict_types=1);

namespace Moox\Static\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Moox\Localization\Models\Localization;
use Pest\Livewire\InteractsWithLivewire;

abstract class FeatureTestCase extends TestCase
{
    use InteractsWithLivewire;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('static.readonly', false);
        $this->seedLocalizationFixture();
    }

    protected function seedLocalizationFixture(): void
    {
        if (! Schema::hasTable('localizations')) {
            Schema::create('localizations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('language_id')->nullable();
                $table->unsignedBigInteger('fallback_language_id')->nullable();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('locale_variant');
                $table->boolean('is_active_admin')->default(true);
                $table->boolean('is_active_frontend')->default(true);
                $table->boolean('is_default')->default(false);
                $table->string('fallback_behaviour')->default('default');
                $table->string('language_routing')->default('path');
                $table->boolean('use_native_names')->default(true);
                $table->boolean('show_regional_variants')->default(true);
                $table->boolean('use_country_translations')->default(true);
                $table->boolean('use_country_icon')->default(false);
                $table->timestamps();
            });
        }

        if (Localization::query()->where('is_default', true)->doesntExist()) {
            Localization::query()->create([
                'title' => 'English',
                'slug' => 'en-us',
                'locale_variant' => 'en_US',
                'is_active_admin' => true,
                'is_active_frontend' => true,
                'is_default' => true,
            ]);
        }
    }
}
