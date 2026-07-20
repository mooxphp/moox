<?php

use Moox\KositValidator\Tests\TestCase;

require_once __DIR__.'/Helpers.php';

pest()->extends(TestCase::class)
    ->beforeEach(function (): void {
        $this->artisan('migrate');
    })->afterEach(function (): void {
        $this->artisan('db:wipe');
        $this->artisan('optimize:clear');
    })->in('Feature', 'Unit');
