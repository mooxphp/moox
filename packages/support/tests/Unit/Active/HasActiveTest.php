<?php

declare(strict_types=1);

use Moox\Support\Tests\Fixtures\ActiveModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

it('adds the boolean cast', function () {
    $model = new ActiveModel();

    expect($model->getCasts())
        ->toHaveKey('is_active', 'boolean');
});

it('creates an active model', function () {
    $model = ActiveModel::create([
        'is_active' => true,
    ]);

    expect($model->exists)->toBeTrue();

    $this->assertDatabaseHas('active_models', [
        'id' => $model->id,
        'is_active' => true,
    ]);
});

it('creates an inactive model', function () {
    $model = ActiveModel::create([
        'is_active' => false,
    ]);

    $this->assertDatabaseHas('active_models', [
        'id' => $model->id,
        'is_active' => false,
    ]);
});

it('returns only active models', function () {
    ActiveModel::create(['is_active' => true]);
    ActiveModel::create(['is_active' => false]);

    expect(ActiveModel::active()->count())->toBe(1);
});

it('returns only inactive models', function () {
    ActiveModel::create(['is_active' => true]);
    ActiveModel::create(['is_active' => false]);

    expect(ActiveModel::inactive()->count())->toBe(1);
});