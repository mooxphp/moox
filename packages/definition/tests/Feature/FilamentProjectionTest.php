<?php

declare(strict_types=1);

namespace Moox\Definition\Tests\Feature;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Moox\Definition\Definitions\Entity;
use Moox\Definition\Definitions\Field;
use Moox\Definition\Definitions\Package;
use Moox\Definition\Definitions\Specifications\TextSpecification;
use Moox\Definition\Filament\Presentation;
use Moox\Definition\Filament\Resource as DefinitionResource;
use Moox\Definition\Projection\IncompleteProjection;
use Moox\Definition\Projection\UnsupportedProjection;
use Moox\Definition\Resolution\Catalog;
use Moox\Definition\Tests\Support\ArticleCatalog;
use Moox\Definition\Tests\Support\ArticleResource;

it('returns native components and keeps customizations on one projection', function (): void {
    $entity = ArticleCatalog::make()->resolve('moox/article', 'article');
    $first = new DefinitionResource($entity);
    $second = new DefinitionResource($entity);
    $number = $first->field('article_number')->formComponent();
    $untouched = $first->field('uuid')->formComponent();

    expect($number)->toBeInstanceOf(TextInput::class)
        ->and($number->getMaxLength())->toBe(32)
        ->and($number->isRequired())->toBeTrue();

    $number->required()->maxLength(16);
    $first->field('notes')->formComponent()->required();
    $first->field('article_number')->tableColumn()->sortable();
    $first->field('article_number')->replaceForm(TextInput::make('article_number')->password());

    $schema = $first->applyForm(definitionSchema());
    $components = $schema->getFlatComponents(withActions: false, withHidden: true);
    $replacement = null;

    foreach ($components as $component) {
        if ($component instanceof TextInput && $component->getName() === 'article_number') {
            $replacement = $component;
        }
    }

    expect($replacement)->not->toBeNull()
        ->and($replacement->isPassword())->toBeTrue()
        ->and($untouched)->toBe($first->field('uuid')->formComponent())
        ->and($untouched->isDisabled())->toBeTrue()
        ->and($untouched->isReadOnly())->toBeTrue()
        ->and($untouched->isDehydrated())->toBeFalse()
        ->and($first->field('article_number')->tableColumn())->toBeInstanceOf(TextColumn::class)
        ->and($first->field('article_number')->tableColumn()->isSortable())->toBeTrue()
        ->and($second->field('article_number')->formComponent()->getMaxLength())->toBe(32)
        ->and($second->field('article_number')->formComponent()->isPassword())->toBeFalse()
        ->and($second->field('article_number')->tableColumn()->isSortable())->toBeFalse()
        ->and($second->field('notes')->formComponent()->isRequired())->toBeFalse()
        ->and($entity->field('article_number')?->specification())->toBeInstanceOf(TextSpecification::class)
        ->and($entity->field('notes')?->attributes()->required())->toBeFalse()
        ->and($first->field('article_number')->formComponent())->not->toBe($second->field('article_number')->formComponent());
});

it('keeps checkbox and toggle as different native components', function (): void {
    $catalog = (new Catalog)
        ->add((new Package('moox/article'))->addEntity(
            Entity::named('article')
                ->addField(Field::checkbox('accepted')->default(false))
                ->addField(Field::checkbox('optional')->nullable())
                ->addField(Field::toggle('enabled')->default(true)),
        ));
    $projection = new DefinitionResource($catalog->resolve('moox/article', 'article'));

    expect($projection->field('accepted')->formComponent())->toBeInstanceOf(Checkbox::class)
        ->and($projection->field('enabled')->formComponent())->toBeInstanceOf(Toggle::class)
        ->and($projection->field('accepted')->formComponent()->getDefaultState())->toBeFalse()
        ->and($projection->field('enabled')->formComponent()->getDefaultState())->toBeTrue()
        ->and($projection->field('optional')->formComponent()->getDefaultState())->toBeNull();
});

it('does not build a relation select without titleAttribute', function (): void {
    $entity = ArticleCatalog::make()->resolve('moox/article', 'article');
    $projection = new DefinitionResource($entity);

    expect(fn () => $projection->relation('productGroup')->formComponent())
        ->toThrow(IncompleteProjection::class, 'missing titleAttribute');
});

it('uses the native select when titleAttribute is configured on the projection', function (): void {
    $entity = ArticleCatalog::make()->resolve('moox/article', 'article');
    $projection = new DefinitionResource($entity, (new Presentation)->relation('productGroup', 'title'));
    $component = $projection->relation('productGroup')->formComponent();

    expect($component->getRelationshipName())->toBe('productGroup')
        ->and($component->getRelationshipTitleAttribute())->toBe('title');
});

it('reports an unsupported filament field without changing the definition', function (): void {
    $entity = Entity::named('article')->addField(Field::richText('body')->nullable());
    $catalog = (new Catalog)
        ->add((new Package('moox/article'))->addEntity($entity));
    $resolved = $catalog->resolve('moox/article', 'article');

    expect(fn () => new DefinitionResource($resolved))->toThrow(UnsupportedProjection::class, 'type [richText]');
});

it('delegates a concrete filament resource to a fresh projection', function (): void {
    $schema = ArticleResource::form(definitionSchema());
    $component = null;

    foreach ($schema->getFlatComponents(withActions: false, withHidden: true) as $candidate) {
        if ($candidate instanceof TextInput && $candidate->getName() === 'article_number') {
            $component = $candidate;
        }
    }

    expect($component)->not->toBeNull()
        ->and($component->getMaxLength())->toBe(16)
        ->and(ArticleCatalog::catalog()->resolve('moox/article', 'article')->field('article_number')?->attributes()->required())->toBeTrue();
});
