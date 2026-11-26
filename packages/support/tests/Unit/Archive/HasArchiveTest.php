<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Moox\Support\Tests\Fixtures\ArchiveModel;
use Moox\Support\Tests\Fixtures\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

it('adds archive columns to the model', function () {
    $model = new ArchiveModel();

    expect($model->getCasts())
        ->toBeArray()
        ->toHaveCount(4)
        ->toHaveKey('archived_at', 'datetime')
        ->toHaveKey('archived_by_id', 'integer')
        ->toHaveKey('archived_by_type', 'string');
});

it('creates an archived model', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($user);

    $model = ArchiveModel::create();
    $model->archive();

    expect($model->isArchived())->toBeTrue()
        ->and($model->archived_at)->toBeInstanceOf(Carbon::class)
        ->and($model->archived_by_id)->toBe($user->id)
        ->and($model->archived_by_type)->toBe($user->getMorphClass());
});

it('unarchives a model', function () {
    $model = ArchiveModel::create();

    $model->archive();
    $model->unarchive();

    expect($model->isArchived())->toBeFalse()
        ->and($model->archived_at)->toBeNull();
});

it('returns only archived models', function () {
    $archived = ArchiveModel::create();
    $archived->archive();

    ArchiveModel::create();

    expect(ArchiveModel::archived()->count())->toBe(1);
});

it('returns only non archived models', function () {
    $model = ArchiveModel::create();
    $model->archive();

    ArchiveModel::create();

    expect(ArchiveModel::notArchived()->count())->toBe(1);
});