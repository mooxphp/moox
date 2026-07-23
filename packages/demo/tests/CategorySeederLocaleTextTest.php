<?php

declare(strict_types=1);

namespace Moox\Demo\Tests;

use Faker\Factory;
use Faker\Generator;
use Moox\Demo\Seeding\FormatsFakerLocaleText;
use PHPUnit\Framework\TestCase;

/**
 * Mirrors CategorySeeder translation field generation for de_DE.
 */
class CategorySeederLocaleTextTest extends TestCase
{
    use FormatsFakerLocaleText;

    public function test_category_de_de_fields_match_seeder_pattern(): void
    {
        $faker = Factory::create('de_DE');

        for ($i = 0; $i < 10; $i++) {
            $title = $this->fakerLocaleTitle('de_DE', $faker, 'title');
            $description = $this->fakerLocaleText('de_DE', $faker, preset: 'description');
            $content = $this->markdownContentFromLocale('de_DE', $faker);

            $bundle = $title.' '.$description.' '.$content;

            $this->assertNotEmpty($title);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(dolorem|voluptat|architecto|lorem|aperiam)\b/i',
                $bundle,
                'Category-style de_DE fields must not use Lorem ipsum tokens',
            );
        }
    }

    public function test_missing_real_text_throws_for_moox_locales(): void
    {
        $faker = new class extends Generator
        {
            public function __construct()
            {
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->fakerLocaleText('de_DE', $faker, preset: 'description');
    }
}
