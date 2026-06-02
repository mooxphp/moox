<?php

declare(strict_types=1);

namespace Moox\Demo\Tests\Unit;

use Moox\Demo\Demo\DemoContext;
use PHPUnit\Framework\TestCase;

class DemoContextTest extends TestCase
{
    public function test_context_holds_dataset_count(): void
    {
        $context = new DemoContext(
            languageCount: 3,
            locales: ['de_DE', 'en_US'],
            dataset: 'small',
            datasetCount: 100,
            fresh: false,
            skipSeeders: false,
            skipFactories: false,
            skipMedia: false,
        );

        $this->assertSame(100, $context->datasetCount);
        $this->assertSame(['de_DE', 'en_US'], $context->locales);
    }
}
