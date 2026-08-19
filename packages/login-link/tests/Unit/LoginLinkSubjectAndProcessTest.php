<?php

declare(strict_types=1);

use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;
use Moox\LoginLink\Tests\Support\TestSubject;
use Moox\LoginLink\Tests\Support\TestUser;
use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    });

    $this->app['db']->connection()->getSchemaBuilder()->create('test_subjects', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->timestamps();
    });
});

it('persists and loads a non-user polymorphic subject', function (): void {
    $subject = TestSubject::query()->create([
        'name' => 'Delivery Address',
        'email' => 'ap@example.com',
    ]);

    $loginLink = LoginLink::query()->create([
        'panel_id' => 'admin',
        'process' => 'verify-address',
        'subject_type' => TestSubject::class,
        'subject_id' => $subject->id,
        'email' => $subject->email,
        'expires_at' => now()->addHour(),
        'used_at' => null,
    ]);

    $fresh = $loginLink->fresh();

    expect($fresh->process)->toBe('verify-address')
        ->and($fresh->subject)->toBeInstanceOf(TestSubject::class)
        ->and($fresh->subject->is($subject))->toBeTrue()
        ->and($fresh->user)->toBeNull();
});

it('defaults process to login and can populate subject alongside the user morph', function (): void {
    $user = TestUser::query()->create([
        'name' => 'Test User',
        'email' => 'subject-user@example.com',
        'password' => bcrypt('secret'),
    ]);

    $loginLink = LoginLink::query()->create([
        'panel_id' => 'admin',
        'user_type' => TestUser::class,
        'user_id' => $user->id,
        'subject_type' => TestUser::class,
        'subject_id' => $user->id,
        'email' => $user->email,
        'expires_at' => now()->addHour(),
    ]);

    $fresh = $loginLink->fresh();

    expect($fresh->process)->toBe(RedemptionHandlerRegistry::DEFAULT_PROCESS)
        ->and($fresh->subject->is($user))->toBeTrue()
        ->and($fresh->user->is($user))->toBeTrue();
});
