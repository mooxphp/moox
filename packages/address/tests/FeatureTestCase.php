<?php

declare(strict_types=1);

namespace Moox\Address\Tests;

use Pest\Livewire\InteractsWithLivewire;

abstract class FeatureTestCase extends TestCase
{
    use InteractsWithLivewire;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('address.taxonomies', []);
        config()->set('address.readonly', false);
    }
}
