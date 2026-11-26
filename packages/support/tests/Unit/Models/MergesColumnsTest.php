<?php

declare(strict_types=1);

use Moox\Support\Tests\Fixtures\MergesColumnsModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

it('merges fillable columns', function () {
    $model = new MergesColumnsModel;

    $model->mergeTestColumns();

    expect($model->getFillable())
        ->toContain('name')
        ->toContain('email');
});

it('merges casts', function () {
    $model = new MergesColumnsModel;

    $model->mergeTestColumns();

    expect($model->getCasts())
        ->toHaveKey('enabled', 'boolean')
        ->toHaveKey('data', 'array');
});

it('merges default attributes', function () {
    $model = new MergesColumnsModel;

    $model->mergeTestColumns();

    expect($model->getAttributes())
        ->toHaveKey('status', 'draft')
        ->toHaveKey('type', 'default');
});

it('does not overwrite existing attributes', function () {
    $model = new MergesColumnsModel;

    $model->status = 'published';

    $model->mergeTestColumns();

    expect($model->getAttributes()['status'])
        ->toBe('published');
});

it('does not remove existing fillable columns or casts', function () {
    $model = new MergesColumnsModel;

    $model->mergeTestColumns();

    expect($model->getFillable())
        ->toContain('name', 'email')
        ->and($model->getCasts())
        ->toHaveKey('enabled', 'boolean')
        ->toHaveKey('data', 'array');
});

