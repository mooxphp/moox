<?php

declare(strict_types=1);

namespace Moox\Address\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Livewire\InteractsWithLivewire;
use Tests\TestCase as AppTestCase;

abstract class FeatureTestCase extends AppTestCase
{
    use InteractsWithLivewire;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('address.taxonomies', []);
        config()->set('address.readonly', false);
    }
}
