<?php

declare(strict_types=1);

use Moox\Support\Tests\Fixtures\SoftDeleteModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);   

it('makes soft delete columns fillable', function (): void {
    $model = new SoftDeleteModel();

    expect($model->getFillable())->toContain(
        'deleted_at',
        'deleted_by_id',
        'deleted_by_type',
        'restored_at',
        'restored_by_id',
        'restored_by_type',
    );
});

it('registers the expected casts', function (): void {
    $model = new SoftDeleteModel();

    expect($model->getCasts())->toMatchArray([
        'deleted_at' => 'datetime',
        'deleted_by_id' => 'integer',
        'restored_at' => 'datetime',
        'restored_by_id' => 'integer',
    ]);
});

it('uses laravel soft deletes', function (): void {
    $model = SoftDeleteModel::query()->create([
        'name' => 'Example',
    ]);

    expect($model->delete())->toBeTrue();

    expect($model->fresh()->trashed())->toBeTrue();

    expect(SoftDeleteModel::query()->find($model->id))
        ->toBeNull();

    expect(SoftDeleteModel::withTrashed()->find($model->id))
        ->not->toBeNull();
});

it('restores a soft deleted model', function (): void {
    $model = SoftDeleteModel::query()->create([
        'name' => 'Example',
    ]);

    $model->delete();

    $deletedModel = SoftDeleteModel::withTrashed()->findOrFail($model->id);

    expect($deletedModel->restore())->toBeTrue();

    expect($deletedModel->fresh()->trashed())->toBeFalse();

    expect(SoftDeleteModel::query()->find($model->id))
        ->not->toBeNull();
});

it('force deletes a model permanently', function (): void {
    $model = SoftDeleteModel::query()->create([
        'name' => 'Example',
    ]);

    $model->delete();

    $deletedModel = SoftDeleteModel::withTrashed()->findOrFail($model->id);

    expect($deletedModel->forceDelete())->toBeTrue();

    expect(SoftDeleteModel::withTrashed()->find($model->id))
        ->toBeNull();
});

it('does not automatically populate restore audit columns', function (): void {
    $model = SoftDeleteModel::query()->create([
        'name' => 'Example',
    ]);

    $model->delete();

    $deletedModel = SoftDeleteModel::withTrashed()->findOrFail($model->id);
    $deletedModel->restore();

    expect($deletedModel->fresh()->restored_at)->toBeNull()
        ->and($deletedModel->fresh()->restored_by_id)->toBeNull()
        ->and($deletedModel->fresh()->restored_by_type)->toBeNull();
});