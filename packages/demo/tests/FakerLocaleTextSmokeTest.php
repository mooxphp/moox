<?php

declare(strict_types=1);

namespace Moox\Demo\Tests;

use Faker\Factory;
use Faker\Generator;
use Moox\Demo\Seeding\FormatsFakerLocaleText;
use PHPUnit\Framework\TestCase;

class FakerLocaleTextSmokeTest extends TestCase
{
    use FormatsFakerLocaleText;

    public function test_de_de_text_avoids_typical_lorem_tokens(): void
    {
        $faker = Factory::create('de_DE');

        for ($i = 0; $i < 5; $i++) {
            $title = $this->fakerLocaleTitle('de_DE', $faker, 'title');
            $description = $this->fakerLocaleText('de_DE', $faker, preset: 'description');

            $this->assertNotEmpty($title);
            $this->assertNotEmpty($description);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(dolorem|voluptat|architecto|lorem)\b/i',
                $title.' '.$description,
                'de_DE demo text should not look like Lorem ipsum',
            );
        }
    }

    public function test_locale_supports_real_text(): void
    {
        $faker = Factory::create('de_DE');

        $this->assertTrue($this->localeSupportsRealText($faker));
        $this->assertIsString($this->fakerLocaleText('de_DE', $faker, 50, 100));
    }
}
