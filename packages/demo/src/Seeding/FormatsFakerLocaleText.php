<?php

declare(strict_types=1);

namespace Moox\Demo\Seeding;

use Faker\Generator;
use Illuminate\Support\Str;
use RuntimeException;

trait FormatsFakerLocaleText
{
    /** @var list<string> */
    private const LOCALES_REQUIRING_REAL_TEXT = ['cs_CZ', 'en_US', 'de_DE', 'pl_PL'];

    /** @var array<string, array{0: int, 1: int}> */
    private const TEXT_PRESET_CHARS = [
        'title' => [13, 35],
        'tag_title' => [8, 23],
        'subtitle' => [20, 50],
        'excerpt' => [40, 90],
        'description' => [40, 70],
        'body' => [35, 80],
        'content' => [55, 120],
    ];

    protected function formatFakerWords(string $locale, Generator $faker, int $minWords, int $maxWords): string
    {
        $preset = $maxWords <= 3 ? 'tag_title' : 'title';

        return $this->fakerLocaleTitle($locale, $faker, $preset);
    }

    protected function formatFakerSentence(string $locale, Generator $faker, int $minWords = 2, int $maxWords = 4): string
    {
        unset($minWords, $maxWords);

        return $this->fakerLocaleTitle($locale, $faker, 'title');
    }

    protected function formatFakerPlainText(string $locale, string $text): string
    {
        if ($locale === 'en_US') {
            return Str::title($text);
        }

        return $text;
    }

    /**
     * Markdown body with heading + paragraphs — locale-sprachig via realText (Locale-Lock).
     */
    protected function markdownContentFromLocale(
        string $locale,
        Generator $localeFaker,
        int $minParagraphs = 3,
        int $maxParagraphs = 6,
        ?int $minChars = null,
        ?int $maxChars = null,
    ): string {
        $heading = $this->fakerLocaleTitle($locale, $localeFaker, 'title');
        $paragraphMin = $minChars ?? self::TEXT_PRESET_CHARS['body'][0];
        $paragraphMax = $maxChars ?? self::TEXT_PRESET_CHARS['body'][1];
        $paragraphs = $this->fakerLocaleParagraphs(
            $locale,
            $localeFaker,
            $minParagraphs,
            $maxParagraphs,
            $paragraphMin,
            $paragraphMax,
        );

        return '## '.$heading."\n\n".implode("\n\n", $paragraphs);
    }

    protected function localeSupportsRealText(Generator $faker): bool
    {
        try {
            $sample = $faker->realTextBetween(20, 40);

            return trim($sample) !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  'title'|'tag_title'  $preset
     */
    protected function fakerLocaleTitle(
        string $locale,
        Generator $faker,
        string $preset = 'title',
    ): string {
        [$minChars, $maxChars] = self::TEXT_PRESET_CHARS[$preset];

        return $this->fakerLocaleSentence($locale, $faker, $minChars, $maxChars);
    }

    /**
     * @param  'title'|'tag_title'|'subtitle'|'excerpt'|'description'|'body'|'content'|null  $preset
     */
    protected function fakerLocaleText(
        string $locale,
        Generator $faker,
        ?int $minChars = null,
        ?int $maxChars = null,
        ?string $preset = null,
        ?int $limit = null,
    ): string {
        if ($preset !== null) {
            [$presetMin, $presetMax] = self::TEXT_PRESET_CHARS[$preset];
            $minChars ??= $presetMin;
            $maxChars ??= $presetMax;
        }

        [$minChars, $maxChars] = $this->normalizeCharRange($minChars ?? 35, $maxChars ?? 80);

        $text = trim($this->generateLocaleFließtext($locale, $faker, $minChars, $maxChars));

        if ($limit !== null) {
            $text = Str::limit($text, $limit, '');
        }

        return $this->formatFakerPlainText($locale, $text);
    }

    protected function fakerLocaleSentence(
        string $locale,
        Generator $faker,
        int $minChars = 13,
        int $maxChars = 35,
    ): string {
        $chunk = $this->generateLocaleFließtext($locale, $faker, $minChars, $maxChars);
        $sentence = $this->extractFirstSentence($chunk);

        return $this->formatFakerPlainText($locale, $sentence);
    }

    /**
     * @return list<string>
     */
    protected function fakerLocaleParagraphs(
        string $locale,
        Generator $faker,
        int $minParagraphs = 3,
        int $maxParagraphs = 6,
        int $minChars = 35,
        int $maxChars = 80,
    ): array {
        $count = random_int(min($minParagraphs, $maxParagraphs), max($minParagraphs, $maxParagraphs));
        $paragraphs = [];

        for ($i = 0; $i < $count; $i++) {
            $paragraphs[] = $this->fakerLocaleText($locale, $faker, $minChars, $maxChars);
        }

        return $paragraphs;
    }

    protected function generateLocaleFließtext(
        string $locale,
        Generator $faker,
        int $minChars,
        int $maxChars,
    ): string {
        $this->assertLocaleUsesRealText($locale, $faker);

        return $faker->realTextBetween($minChars, $maxChars);
    }

    protected function assertLocaleUsesRealText(string $locale, Generator $faker): void
    {
        if (! in_array($locale, self::LOCALES_REQUIRING_REAL_TEXT, true)) {
            return;
        }

        if ($this->localeSupportsRealText($faker)) {
            return;
        }

        throw new RuntimeException(
            "Faker realTextBetween is required for locale [{$locale}] but is not available. ".
            'Use Faker\\Factory::create(\''.$locale.'\') via fakerForLocale().'
        );
    }

    protected function extractFirstSentence(string $text): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        $parts = preg_split('/(?<=[.!?…])\s+/u', $text, 2);

        return trim($parts[0] ?? $text);
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected function normalizeCharRange(int $minChars, int $maxChars): array
    {
        if ($minChars > $maxChars) {
            return [$maxChars, $minChars];
        }

        return [$minChars, $maxChars];
    }
}
