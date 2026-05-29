<?php

declare(strict_types=1);

namespace Moox\Contact\Tests;

use Pest\Livewire\InteractsWithLivewire;

abstract class FeatureTestCase extends TestCase
{
    use InteractsWithLivewire;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('company.taxonomies', []);
        config()->set('company.readonly', false);
    }
}
