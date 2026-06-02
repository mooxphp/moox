<?php

declare(strict_types=1);

use Moox\Transform\Filament\Resources\TransformDefinitionResource;
use Moox\Transform\Filament\Resources\TransformDefinitionResource\Pages\CreateTransformDefinition;
use Moox\Transform\Filament\Resources\TransformDefinitionResource\Pages\EditTransformDefinition;
use Moox\Transform\Filament\Resources\TransformDefinitionResource\Pages\ListTransformDefinitions;
use Moox\Transform\Filament\Resources\TransformDefinitionResource\Pages\ViewTransformDefinition;
use Moox\Transform\Filament\Resources\TransformDefinitionResource\RelationManagers\TransformRecordsRelationManager;
use Moox\Transform\Models\TransformDefinition;

test('it maps to the correct model and pages', function (): void {
    expect(TransformDefinitionResource::getModel())->toBe(TransformDefinition::class);

    $pages = TransformDefinitionResource::getPages();
    expect($pages['index']->getPage())->toBe(ListTransformDefinitions::class);
    expect($pages['create']->getPage())->toBe(CreateTransformDefinition::class);
    expect($pages['edit']->getPage())->toBe(EditTransformDefinition::class);
    expect($pages['view']->getPage())->toBe(ViewTransformDefinition::class);
});

test('it exposes relation managers for definition records', function (): void {
    $relations = TransformDefinitionResource::getRelations();

    expect($relations)->toContain(TransformRecordsRelationManager::class);
});
