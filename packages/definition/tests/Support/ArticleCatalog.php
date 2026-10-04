<?php

declare(strict_types=1);

namespace Moox\Definition\Tests\Support;

use Moox\Definition\Definitions\Entity;
use Moox\Definition\Definitions\EntityReference;
use Moox\Definition\Definitions\Field;
use Moox\Definition\Definitions\Package;
use Moox\Definition\Definitions\Relation;
use Moox\Definition\Definitions\RelationKeys;
use Moox\Definition\Definitions\RelationType;
use Moox\Definition\Features\Identity\Identity;
use Moox\Definition\Resolution\Catalog;

final class ArticleCatalog
{
    private static ?Catalog $catalog = null;

    public static function catalog(): Catalog
    {
        return self::$catalog ??= self::make();
    }

    public static function make(): Catalog
    {
        $group = Entity::named('productGroup')
            ->table('product_groups')
            ->addFeature(new Identity);

        $article = Entity::named('article')
            ->table('articles')
            ->addFeature(new Identity)
            ->addFeature(new NotesFeature)
            ->addField(
                Field::text('article_number', 32)
                    ->label('Article number')
                    ->required()
                    ->unique(),
            )
            ->addField(Field::dateTime('available_at')->nullable())
            ->addRelation(new Relation(
                name: 'productGroup',
                type: RelationType::BelongsTo,
                target: new EntityReference('moox/product-group', 'productGroup', 'ulid'),
                through: null,
                keys: new RelationKeys(
                    foreignKey: 'product_group_ulid',
                    ownerKey: 'ulid',
                ),
                pivot: null,
                inverse: null,
                nullable: false,
            ));

        return (new Catalog)
            ->add((new Package('moox/product-group', 'Moox Product Group'))->addEntity($group))
            ->add((new Package('moox/article', 'Moox Article'))->addEntity($article));
    }
}
