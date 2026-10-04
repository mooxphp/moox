<?php

declare(strict_types=1);

namespace Moox\Definition\Tests\Unit;

use Moox\Definition\Definitions\Entity;
use Moox\Definition\Definitions\EntityReference;
use Moox\Definition\Definitions\Feature;
use Moox\Definition\Definitions\Field;
use Moox\Definition\Definitions\FieldRole;
use Moox\Definition\Definitions\Package;
use Moox\Definition\Definitions\Relation;
use Moox\Definition\Definitions\RelationKeys;
use Moox\Definition\Definitions\RelationType;
use Moox\Definition\Features\Identity\Identity;
use Moox\Definition\Projection\StorageKind;
use Moox\Definition\Resolution\Catalog;
use Moox\Definition\Resolution\CompositionConflict;
use Moox\Definition\Tests\Support\ArticleCatalog;
use Moox\Definition\Validation\InvalidDefinition;

it('resolves identity semantics and leaves the declaration unchanged', function (): void {
    $entity = Entity::named('article')
        ->table('articles')
        ->addFeature(new Identity)
        ->addField(Field::text('article_number', 32)->required());
    $declared = array_keys($entity->fields());
    $catalog = (new Catalog)->add((new Package('moox/article'))->addEntity($entity));

    $first = $catalog->resolve('moox/article', 'article');
    $second = $catalog->resolve('moox/article', 'article');

    expect($declared)->toBe(['article_number'])
        ->and(array_keys($entity->fields()))->toBe($declared)
        ->and(array_keys($first->fields()))->toBe(['id', 'ulid', 'uuid', 'article_number'])
        ->and(array_keys($second->fields()))->toBe(array_keys($first->fields()))
        ->and($first->field('id')?->attributes()->systemManaged())->toBeTrue()
        ->and($first->field('id')?->attributes()->immutable())->toBeTrue()
        ->and($first->field('id')?->attributes()->generator()->value())->toBe('primary')
        ->and($first->field('id')?->attributes()->role())->toBe(FieldRole::PrimaryIdentifier)
        ->and($first->field('ulid')?->attributes()->generator()->value())->toBe('ulid')
        ->and($first->field('uuid')?->attributes()->unique())->toBeTrue()
        ->and($first->field('uuid')?->attributes()->immutable())->toBeTrue()
        ->and($first->field('uuid')?->attributes()->systemManaged())->toBeTrue()
        ->and($first->field('notes'))->toBeNull()
        ->and($first->origin('article_number')?->isDomain())->toBeTrue()
        ->and($first->origin('uuid')?->featureKey())->toBe('identity');
});

it('does not make a feature field system-managed', function (): void {
    $resolved = ArticleCatalog::make()->resolve('moox/article', 'article');

    expect($resolved->field('notes')?->attributes()->systemManaged())->toBeFalse()
        ->and($resolved->origin('notes')?->featureKey())->toBe('notes');
});

it('reports an accidental duplicate instead of replacing a feature field', function (): void {
    $entity = Entity::named('article')
        ->addFeature(new Identity)
        ->addField(Field::text('id', 8)->required());
    $catalog = (new Catalog)->add((new Package('moox/article'))->addEntity($entity));

    expect(fn () => $catalog->resolve('moox/article', 'article'))
        ->toThrow(CompositionConflict::class, 'contributed by feature [identity]');
});

it('accepts an explicit override and keeps repeated resolution stable', function (): void {
    $entity = Entity::named('article')
        ->table('articles')
        ->addFeature(new Identity)
        ->override('uuid')
        ->addField(
            Field::uuid('uuid')
                ->required()
                ->unique()
                ->immutable()
                ->label('External UUID'),
        );
    $catalog = (new Catalog)->add((new Package('moox/article'))->addEntity($entity));
    $resolved = $catalog->resolve('moox/article', 'article');

    expect($resolved->origin('uuid')?->isDomain())->toBeTrue()
        ->and($resolved->field('uuid')?->attributes()->label())->toBe('External UUID')
        ->and($resolved->field('uuid')?->attributes()->systemManaged())->toBeFalse()
        ->and(array_keys($catalog->resolve('moox/article', 'article')->fields()))->toBe(array_keys($resolved->fields()));
});

it('rejects an override that does not replace a contribution', function (): void {
    $entity = Entity::named('article')
        ->addFeature(new Identity)
        ->override('missing')
        ->addField(Field::text('article_number', 32)->required());
    $catalog = (new Catalog)->add((new Package('moox/article'))->addEntity($entity));

    expect(fn () => $catalog->resolve('moox/article', 'article'))
        ->toThrow(CompositionConflict::class, 'Override [missing]');
});

it('resolves a cross-package reference from the declared target field', function (): void {
    $resolved = ArticleCatalog::make()->resolve('moox/article', 'article');
    $relation = $resolved->relation('productGroup');

    expect($relation?->localColumn()?->kind())->toBe(StorageKind::Ulid)
        ->and($relation?->relation()->keys()->foreignKey())->toBe('product_group_ulid')
        ->and($relation?->relation()->keys()->ownerKey())->toBe('ulid');
});

it('reports a missing target package and does not substitute id', function (): void {
    $entity = Entity::named('article')
        ->addFeature(new Identity)
        ->addRelation(new Relation(
            name: 'productGroup',
            type: RelationType::BelongsTo,
            target: new EntityReference('moox/product-group', 'productGroup', 'ulid'),
            through: null,
            keys: new RelationKeys(foreignKey: 'product_group_ulid', ownerKey: 'ulid'),
            pivot: null,
            inverse: null,
            nullable: false,
        ));
    $catalog = (new Catalog)->add((new Package('moox/article'))->addEntity($entity));

    expect(fn () => $catalog->resolve('moox/article', 'article'))
        ->toThrow(InvalidDefinition::class, 'Target package [moox/product-group] is not registered.');
});

it('reports a missing target field by its declared name', function (): void {
    $group = Entity::named('productGroup')->addFeature(new Identity);
    $article = Entity::named('article')
        ->addFeature(new Identity)
        ->addRelation(new Relation(
            name: 'productGroup',
            type: RelationType::BelongsTo,
            target: new EntityReference('moox/product-group', 'productGroup', 'sku'),
            through: null,
            keys: new RelationKeys(foreignKey: 'product_group_sku', ownerKey: 'sku'),
            pivot: null,
            inverse: null,
            nullable: true,
        ));
    $catalog = (new Catalog)
        ->add((new Package('moox/product-group'))->addEntity($group))
        ->add((new Package('moox/article'))->addEntity($article));

    expect(fn () => $catalog->resolve('moox/article', 'article'))
        ->toThrow(InvalidDefinition::class, 'Target field [sku] is not defined on [moox/product-group.productGroup].');
});

it('reports an owner key that disagrees with the referenced field', function (): void {
    $group = Entity::named('productGroup')->addFeature(new Identity);
    $article = Entity::named('article')
        ->addFeature(new Identity)
        ->addRelation(new Relation(
            name: 'productGroup',
            type: RelationType::BelongsTo,
            target: new EntityReference('moox/product-group', 'productGroup', 'ulid'),
            through: null,
            keys: new RelationKeys(foreignKey: 'product_group_id', ownerKey: 'id'),
            pivot: null,
            inverse: null,
            nullable: false,
        ));
    $catalog = (new Catalog)
        ->add((new Package('moox/product-group'))->addEntity($group))
        ->add((new Package('moox/article'))->addEntity($article));

    expect(fn () => $catalog->resolve('moox/article', 'article'))
        ->toThrow(InvalidDefinition::class, 'ownerKey [id] does not match the referenced target field [ulid]');
});

it('reports every missing relation key', function (): void {
    $group = Entity::named('productGroup')->addFeature(new Identity);
    $article = Entity::named('article')
        ->addFeature(new Identity)
        ->addRelation(new Relation(
            name: 'productGroup',
            type: RelationType::BelongsTo,
            target: new EntityReference('moox/product-group', 'productGroup', 'ulid'),
            through: null,
            keys: new RelationKeys,
            pivot: null,
            inverse: null,
            nullable: false,
        ));
    $catalog = (new Catalog)
        ->add((new Package('moox/product-group'))->addEntity($group))
        ->add((new Package('moox/article'))->addEntity($article));

    try {
        $catalog->resolve('moox/article', 'article');
        throw new RuntimeException('Relation resolved without keys.');
    } catch (InvalidDefinition $exception) {
        expect($exception->getMessage())->toContain('foreignKey')->toContain('ownerKey');
    }
});

it('keeps a hasMany foreign key on the referenced entity', function (): void {
    $comment = Entity::named('comment')->addFeature(new Identity);
    $article = Entity::named('article')
        ->addFeature(new Identity)
        ->addRelation(new Relation(
            name: 'comments',
            type: RelationType::HasMany,
            target: new EntityReference('moox/comment', 'comment', 'article_id'),
            through: null,
            keys: new RelationKeys(foreignKey: 'article_id', localKey: 'id'),
            pivot: null,
            inverse: null,
            nullable: true,
        ));
    $catalog = (new Catalog)
        ->add((new Package('moox/comment'))->addEntity($comment))
        ->add((new Package('moox/article'))->addEntity($article));
    $resolved = $catalog->resolve('moox/article', 'article');

    expect($resolved->relation('comments')?->localColumn())->toBeNull()
        ->and($resolved->relation('comments')?->relation()->keys()->foreignKey())->toBe('article_id');
});

it('detects two features contributing the same field', function (): void {
    $second = new class extends Feature
    {
        public static function key(): string
        {
            return 'other-identity';
        }

        public function apply(Entity $entity): void
        {
            $entity->addField(Field::uuid('uuid')->unique()->immutable());
        }
    };
    $entity = Entity::named('article')->addFeature(new Identity)->addFeature($second);
    $catalog = (new Catalog)->add((new Package('moox/article'))->addEntity($entity));

    expect(fn () => $catalog->resolve('moox/article', 'article'))
        ->toThrow(CompositionConflict::class, 'feature [identity] and feature [other-identity]');
});
