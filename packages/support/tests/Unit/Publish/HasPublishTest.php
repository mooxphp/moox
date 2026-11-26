<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Moox\Support\Tests\Fixtures\PublishModel;
use Moox\Support\Tests\Fixtures\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

it('registers publish casts', function () {
    $model = new PublishModel;

    expect($model->getCasts())
        ->toHaveKey('published_at', 'datetime')
        ->toHaveKey('published_by_id', 'integer')
        ->toHaveKey('published_by_type', 'string')
        ->toHaveKey('unpublished_at', 'datetime')
        ->toHaveKey('unpublished_by_id', 'integer')
        ->toHaveKey('unpublished_by_type', 'string');
});

it('publishes a model', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($user);

    $model = PublishModel::create();
    $model->publish();

    expect($model->isPublished())->toBeTrue()
        ->and($model->published_at)->toBeInstanceOf(Carbon::class)
        ->and($model->published_by_id)->toBe($user->id)
        ->and($model->published_by_type)->toBe($user->getMorphClass());
});

it('unpublishes a model', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($user);

    $model = PublishModel::create();
    $model->publish();
    $model->unpublish();

    expect($model->isPublished())->toBeFalse()
        ->and($model->isUnpublished())->toBeTrue()
        ->and($model->unpublished_at)->toBeInstanceOf(Carbon::class)
        ->and($model->unpublished_by_id)->toBe($user->id)
        ->and($model->unpublished_by_type)->toBe($user->getMorphClass());
});

it('resets unpublished fields when publishing again', function () {
    $model = PublishModel::create();

    $model->publish();
    $model->unpublish();
    $model->publish();

    expect($model->isPublished())->toBeTrue()
        ->and($model->unpublished_at)->toBeNull()
        ->and($model->unpublished_by_id)->toBeNull()
        ->and($model->unpublished_by_type)->toBeNull();
});

it('detects an unpublished model', function () {
    $model = PublishModel::create();

    expect($model->isPublished())->toBeFalse()
        ->and($model->isUnpublished())->toBeFalse();
});

it('filters published models', function () {
    $published = PublishModel::create();
    $published->publish();

    $unpublished = PublishModel::create();
    $unpublished->publish();
    $unpublished->unpublish();

    PublishModel::create();

    expect(PublishModel::query()->published()->count())->toBe(1)
        ->and(PublishModel::query()->published()->first()->is($published))->toBeTrue();
});

it('filters unpublished models', function () {
    $model = PublishModel::create();
    $model->publish();
    $model->unpublish();

    expect(PublishModel::query()->unpublished()->count())->toBe(1);
});

it('filters models that are not currently published', function () {
    $published = PublishModel::create();
    $published->publish();

    $unpublished = PublishModel::create();
    $unpublished->publish();
    $unpublished->unpublish();

    PublishModel::create();

    expect(PublishModel::query()->notPublished()->count())->toBe(2);
});