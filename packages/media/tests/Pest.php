<?php

use Moox\Media\Tests\TestCase;
use Workbench\App\Models\User;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extends(TestCase::class)
    ->beforeEach(function (): void {
        // @phpstan-ignore-next-line variable.undefined (Pest bindet $this zur Laufzeit)
        $this->artisan('migrate');
        // @phpstan-ignore-next-line class.notFound (Workbench\User nur im Test-Workbench)
        $user = User::factory()->create();
        // @phpstan-ignore-next-line variable.undefined (Pest bindet $this zur Laufzeit)
        $this->actingAs($user);
    })->afterEach(function (): void {
        // @phpstan-ignore-next-line variable.undefined (Pest bindet $this zur Laufzeit)
        $this->artisan('db:wipe');
        // @phpstan-ignore-next-line variable.undefined (Pest bindet $this zur Laufzeit)
        $this->artisan('optimize:clear');
    })->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/
