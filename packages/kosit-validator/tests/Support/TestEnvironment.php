<?php

declare(strict_types=1);

namespace Moox\KositValidator\Tests\Support;

use Moox\DevTools\Models\TestUser;
use Moox\KositValidator\Models\KositValidation;

final class TestEnvironment
{
    public static function makeTestUser(array $attributes = []): TestUser
    {
        return TestUser::query()->create(array_merge([
            'name' => 'Test User',
            'email' => 'test-'.uniqid('', true).'@example.com',
            'password' => 'password',
        ], $attributes));
    }

    public static function makeKositValidation(array $attributes = []): KositValidation
    {
        return KositValidation::query()->create(array_merge([
            'input_path' => '/tmp/test-invoice.xml',
            'passed' => true,
            'validated_at' => now(),
        ], $attributes));
    }
}
