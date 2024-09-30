<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\User\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
    }
}
