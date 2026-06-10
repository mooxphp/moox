<?php

declare(strict_types=1);

namespace Moox\Demo\Tests\Unit;

use Moox\Demo\Demo\SeederOrderResolver;
use PHPUnit\Framework\TestCase;

class SeederOrderResolverTest extends TestCase
{
    public function test_data_legacy_slug_comes_before_category_when_both_present(): void
    {
        $resolver = new SeederOrderResolver;
        $ordered = $resolver->resolve();

        $slugs = array_column($ordered, 'slug');

        if (! in_array('data-legacy', $slugs, true) || ! in_array('category', $slugs, true)) {
            $this->markTestSkipped('moox/data-legacy or moox/category not in resolved providers for this environment.');
        }

        $dataLegacyIndex = array_search('data-legacy', $slugs, true);
        $categoryIndex = array_search('category', $slugs, true);

        $this->assertNotFalse($dataLegacyIndex);
        $this->assertNotFalse($categoryIndex);
        $this->assertLessThan($categoryIndex, $dataLegacyIndex);
    }
}
