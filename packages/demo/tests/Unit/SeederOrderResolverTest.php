<?php

declare(strict_types=1);

namespace Moox\Demo\Tests\Unit;

use Moox\Demo\Demo\SeederOrderResolver;
use PHPUnit\Framework\TestCase;

class SeederOrderResolverTest extends TestCase
{
    public function test_data_slug_comes_before_category_when_both_present(): void
    {
        $resolver = new SeederOrderResolver;
        $ordered = $resolver->resolve();

        $slugs = array_column($ordered, 'slug');

        if (! in_array('data', $slugs, true) || ! in_array('category', $slugs, true)) {
            $this->markTestSkipped('moox/data or moox/category not in resolved providers for this environment.');
        }

        $dataIndex = array_search('data', $slugs, true);
        $categoryIndex = array_search('category', $slugs, true);

        $this->assertNotFalse($dataIndex);
        $this->assertNotFalse($categoryIndex);
        $this->assertLessThan($categoryIndex, $dataIndex);
    }
}
