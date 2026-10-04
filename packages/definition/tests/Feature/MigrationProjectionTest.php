<?php

declare(strict_types=1);

namespace Moox\Definition\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Moox\Definition\Definitions\Entity;
use Moox\Definition\Definitions\EntityReference;
use Moox\Definition\Definitions\Field;
use Moox\Definition\Definitions\Package;
use Moox\Definition\Features\Identity\Identity;
use Moox\Definition\Laravel\Migration;
use Moox\Definition\Projection\IncompleteProjection;
use Moox\Definition\Projection\UnsupportedProjection;
use Moox\Definition\Resolution\Catalog;
use Moox\Definition\Tests\Support\ArticleCatalog;

it('projects identity, text, and the referenced ulid column', function (): void {
    $catalog = ArticleCatalog::make();
    $blueprint = new Blueprint(Schema::getConnection(), 'articles');
    (new Migration)->apply($blueprint, $catalog->resolve('moox/article', 'article'), $catalog);
    $columns = [];

    foreach ($blueprint->getColumns() as $column) {
        $columns[$column->name] = $column;
    }

    expect($columns)->toHaveKeys(['id', 'ulid', 'uuid', 'article_number', 'notes', 'available_at', 'product_group_ulid'])
        ->and($columns['id']->autoIncrement)->toBeTrue()
        ->and($columns['id']->type)->toBe('bigInteger')
        ->and($columns['uuid']->type)->toBe('uuid')
        ->and($columns['ulid']->type)->toBe('char')
        ->and($columns['ulid']->length)->toBe(26)
        ->and($columns['article_number']->type)->toBe('string')
        ->and($columns['article_number']->length)->toBe(32)
        ->and($columns['notes']->nullable)->toBeTrue()
        ->and($columns['available_at']->type)->toBe('timestamp')
        ->and($columns['product_group_ulid']->type)->toBe('char')
        ->and($columns['product_group_ulid']->length)->toBe(26)
        ->and(collect($blueprint->getCommands())->contains(fn (mixed $command): bool => is_object($command) && $command->name === 'foreign' && $command->columns === ['product_group_ulid'] && $command->references === 'ulid' && $command->on === 'product_groups'))->toBeTrue();

    Schema::create('product_groups', function (Blueprint $table) use ($catalog): void {
        (new Migration)->apply($table, $catalog->resolve('moox/product-group', 'productGroup'), $catalog);
    });
    Schema::create('articles', function (Blueprint $table) use ($catalog): void {
        (new Migration)->apply($table, $catalog->resolve('moox/article', 'article'), $catalog);
    });

    expect(Schema::hasColumn('articles', 'product_group_ulid'))->toBeTrue()
        ->and(Schema::hasColumn('articles', 'id'))->toBeTrue();
});

it('does not require a filament title attribute to migrate a relation', function (): void {
    $catalog = ArticleCatalog::make();
    $blueprint = new Blueprint(Schema::getConnection(), 'articles');

    (new Migration)->apply($blueprint, $catalog->resolve('moox/article', 'article'), $catalog);

    expect(collect($blueprint->getColumns())->pluck('name'))->toContain('product_group_ulid');
});

it('reports a missing table name', function (): void {
    $entity = Entity::named('article')->addFeature(new Identity);
    $catalog = (new Catalog)->add((new Package('moox/article'))->addEntity($entity));

    expect(fn () => (new Migration)->apply(new Blueprint(Schema::getConnection(), 'articles'), $catalog->resolve('moox/article', 'article'), $catalog))
        ->toThrow(IncompleteProjection::class, 'missing a table name');
});

it('reports unsupported image storage instead of creating an id column', function (): void {
    $group = Entity::named('productGroup')->table('product_groups')->addFeature(new Identity);
    $article = Entity::named('article')
        ->table('articles')
        ->addFeature(new Identity)
        ->addField(Field::image('photo', new EntityReference('moox/product-group', 'productGroup', 'ulid'))->nullable());
    $catalog = (new Catalog)
        ->add((new Package('moox/product-group'))->addEntity($group))
        ->add((new Package('moox/article'))->addEntity($article));

    expect(fn () => (new Migration)->apply(new Blueprint(Schema::getConnection(), 'articles'), $catalog->resolve('moox/article', 'article'), $catalog))
        ->toThrow(UnsupportedProjection::class, 'type [image]');
});
