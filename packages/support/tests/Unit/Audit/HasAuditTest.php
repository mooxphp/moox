<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Moox\Support\Tests\Fixtures\AuditModel;
use Moox\Support\Tests\Fixtures\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

it('adds audit casts to the model', function () {
    $model = new AuditModel();

    expect($model->getCasts())
        ->toHaveKey('created_by_id', 'integer')
        ->toHaveKey('created_by_type', 'string')
        ->toHaveKey('updated_by_id', 'integer')
        ->toHaveKey('updated_by_type', 'string');
});

it('creates an audited model', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($user);

    $model = AuditModel::create();

    expect($model->created_by_id)->toBe($user->id)
        ->and($model->created_by_type)->toBe($user->getMorphClass())
        ->and($model->updated_by_id)->toBe($user->id)
        ->and($model->updated_by_type)->toBe($user->getMorphClass());
});

it('updates the updated by fields', function () {
    $firstUser = User::create([
        'name' => 'First User',
        'email' => 'first@example.com',
        'password' => bcrypt('password'),
    ]);

    $secondUser = User::create([
        'name' => 'Second User',
        'email' => 'second@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($firstUser);

    $model = AuditModel::create();

    $this->actingAs($secondUser);

    $model->name = 'Test Name';
    $model->save();

    expect($model->created_by_id)->toBe($firstUser->id)
        ->and($model->updated_by_id)->toBe($secondUser->id)
        ->and($model->created_by_type)->toBe($firstUser->getMorphClass())
        ->and($model->updated_by_type)->toBe($secondUser->getMorphClass());
});

it('prevents changing the created by fields', function () {
    $firstUser = User::create([
        'name' => 'First User',
        'email' => 'first@example.com',
        'password' => bcrypt('password'),
    ]);

    $secondUser = User::create([
        'name' => 'Second User',
        'email' => 'second@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($firstUser);

    $model = AuditModel::create();

    $this->actingAs($secondUser);

    $model->created_by_id = $secondUser->id;
    $model->created_by_type = $secondUser->getMorphClass();

    $model->save();

    expect($model->created_by_id)->toBe($firstUser->id)
        ->and($model->created_by_type)->toBe($firstUser->getMorphClass());
});

it('provides the created by relationship', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($user);

    $model = AuditModel::create();

    expect($model->createdBy)->toBeInstanceOf(User::class)
        ->and($model->createdBy->id)->toBe($user->id);
});

it('provides the updated by relationship', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($user);

    $model = AuditModel::create();

    expect($model->updatedBy)->toBeInstanceOf(User::class)
        ->and($model->updatedBy->id)->toBe($user->id);
});

it('filters models by creator', function () {
    $firstUser = User::create([
        'name' => 'First User',
        'email' => 'first@example.com',
        'password' => bcrypt('password'),
    ]);

    $secondUser = User::create([
        'name' => 'Second User',
        'email' => 'second@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($firstUser);
    AuditModel::create();

    $this->actingAs($secondUser);
    AuditModel::create();

    expect(AuditModel::query()->createdBy($firstUser)->count())->toBe(1)
        ->and(AuditModel::query()->createdBy($secondUser)->count())->toBe(1);
});

it('does not overwrite audit fields without an authenticated user', function () {
    $model = AuditModel::create();

    expect($model->created_by_id)->toBeNull()
        ->and($model->created_by_type)->toBeNull()
        ->and($model->updated_by_id)->toBeNull()
        ->and($model->updated_by_type)->toBeNull();
});