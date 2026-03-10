<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\Monorepo\Tests\TestCase;

uses(
    TestCase::class,
    RefreshDatabase::class,
)->in('Feature');

uses(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function something()
{
    // ..
}
