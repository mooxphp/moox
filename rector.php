<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\StrictArrayParamDimFetchRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/app',
        __DIR__.'/config',
        __DIR__.'/packages',
    ]);

    $rectorConfig->skip([]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
        SetList::TYPE_DECLARATION,
        SetList::CODING_STYLE,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
    ]);

    $rectorConfig->rule(TypedPropertyFromStrictConstructorRector::class);
    $rectorConfig->rule(StrictArrayParamDimFetchRector::class);
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);
    $rectorConfig->rule(AddVoidReturnTypeWhereNoReturnRector::class);
    $rectorConfig->rule(ReturnTypeFromStrictTypedCallRector::class);
    $rectorConfig->rule(AddReturnTypeDeclarationRector::class);

    $rectorConfig->importNames();
};
