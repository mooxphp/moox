<?php

namespace Moox\Tag\Database\Seeders;

use Illuminate\Database\Seeder;
use Moox\Tag\Models\Tag;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        // Create system tags with all translations
        Tag::factory()
            ->count(3)
            ->system()
            ->withAllTranslations()
            ->create();

        // Create featured tags with specific translations
        Tag::factory()
            ->count(5)
            ->featured()
            ->withGermanTranslation()
            ->withFrenchTranslation()
            ->create();

        // Create tags with random translations
        Tag::factory()
            ->count(10)
            ->withRandomTranslations(3)
            ->create();

        // Create tags with specific language combinations
        Tag::factory()
            ->count(2)
            ->withSpanishTranslation()
            ->withItalianTranslation()
            ->create();

        // Create Dutch-only tags
        Tag::factory()
            ->count(2)
            ->withDutchTranslation()
            ->create();
    }
} 