<?php

declare(strict_types=1);

use Moox\Support\Tests\Fixtures\DataModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);


it('registers the data column as an array cast', function () {
    $model = new DataModel;

    expect($model->getCasts())
        ->toHaveKey('data', 'array');
});

it('stores data as json and retrieves it as an array', function () {
    $data = [
        'key' => 'value',
        'enabled' => true,
        'settings' => [
            'color' => 'blue',
        ],
    ];

    $model = DataModel::create([
        'data' => $data,
    ]);

    $model->refresh();

    expect($model->data)
        ->toBe($data);
});

it('allows null data', function () {
    $model = DataModel::create([
        'data' => null,
    ]);

    $model->refresh();

    expect($model->data)->toBeNull();
});

it('updates existing data', function () {
    $model = DataModel::create([
        'data' => [
            'key' => 'old',
        ],
    ]);

    $model->update([
        'data' => [
            'key' => 'new',
        ],
    ]);

    $model->refresh();

    expect($model->data)->toBe([
        'key' => 'new',
    ]);
});