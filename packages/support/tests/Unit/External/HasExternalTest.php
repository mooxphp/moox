<?php

declare(strict_types=1);

use Moox\Support\Tests\Fixtures\ExternalModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

it('registers the external columns', function () {
    $model = new ExternalModel;

    expect($model->getFillable())
        ->toContain('external_reference')
        ->toContain('external_info')
        ->toContain('external_status');
});

it('detects an external reference', function () {
    $model = new ExternalModel([
        'external_reference' => 'ERP-123',
    ]);

    expect($model->hasExternalReference())->toBeTrue();
});

it('detects external information', function () {
    $model = new ExternalModel([
        'external_info' => 'Imported from ERP',
    ]);

    expect($model->hasExternalInfo())->toBeTrue();
});

it('detects an external status', function () {
    $model = new ExternalModel([
        'external_status' => 'synchronized',
    ]);

    expect($model->hasExternalStatus())->toBeTrue();
});

it('returns false when external values are empty', function () {
    $model = new ExternalModel;

    expect($model->hasExternalReference())->toBeFalse()
        ->and($model->hasExternalInfo())->toBeFalse()
        ->and($model->hasExternalStatus())->toBeFalse();
});

it('stores and retrieves external values', function () {
    $model = ExternalModel::create([
        'external_reference' => 'ERP-123',
        'external_info' => 'Imported from ERP',
        'external_status' => 'synchronized',
    ]);

    $model->refresh();

    expect($model->external_reference)->toBe('ERP-123')
        ->and($model->external_info)->toBe('Imported from ERP')
        ->and($model->external_status)->toBe('synchronized');
});