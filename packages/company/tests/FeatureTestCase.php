<?php

declare(strict_types=1);

namespace Moox\Company\Tests;

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

        config()->set('company.taxonomies', []);
        config()->set('company.readonly', false);
    }
}
