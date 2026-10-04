<?php

declare(strict_types=1);

namespace Moox\Definition\Filament;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table as FilamentTable;
use Moox\Definition\Features\Identity\IdentityFilament;
use Moox\Definition\Projection\IncompleteProjection;
use Moox\Definition\Resolution\ResolvedEntity;

final class Resource
{
    /** @var array<string, Component> */
    private array $formComponents;

    /** @var array<string, Column> */
    private array $tableColumns;

    /** @var array<string, Select> */
    private array $relationComponents;

    public function __construct(
        private readonly ResolvedEntity $entity,
        private readonly Presentation $presentation = new Presentation,
        private readonly ComponentFactory $factory = new ComponentFactory,
    ) {
        $this->formComponents = [];
        $this->tableColumns = [];
        $this->relationComponents = [];

        foreach ($entity->fields() as $name => $field) {
            $this->formComponents[$name] = $this->factory->formComponent($field);
            $this->tableColumns[$name] = $this->factory->tableColumn($field);
        }

        foreach ($entity->relations() as $name => $relation) {
            $title = $this->presentation->titleAttribute($name);

            if ($title === null) {
                continue;
            }

            $this->relationComponents[$name] = $this->factory->relationFormComponent($relation->relation(), $title);
        }

        if ($entity->hasFeature('identity')) {
            (new IdentityFilament)->apply($this);
        }
    }

    public function entity(): ResolvedEntity
    {
        return $this->entity;
    }

    public function field(string $name): FieldHandle
    {
        if (! isset($this->formComponents[$name])) {
            throw new IncompleteProjection("Field [{$name}] is not defined on [{$this->entity->name()}].");
        }

        return new FieldHandle($this, $name);
    }

    public function relation(string $name): RelationHandle
    {
        if ($this->entity->relation($name) === null) {
            throw new IncompleteProjection("Relation [{$name}] is not defined on [{$this->entity->name()}].");
        }

        return new RelationHandle($this, $name);
    }

    public function formComponent(string $name): Component
    {
        return $this->formComponents[$name] ?? throw new IncompleteProjection("Field [{$name}] is not defined on [{$this->entity->name()}].");
    }

    public function tableColumn(string $name): Column
    {
        return $this->tableColumns[$name] ?? throw new IncompleteProjection("Field [{$name}] is not defined on [{$this->entity->name()}].");
    }

    public function relationFormComponent(string $name): Select
    {
        return $this->relationComponents[$name] ?? $this->factory->relationFormComponent(
            $this->entity->relation($name)?->relation() ?? throw new IncompleteProjection("Relation [{$name}] is not defined on [{$this->entity->name()}]."),
            $this->presentation->titleAttribute($name),
        );
    }

    public function replaceForm(string $name, Component $component): void
    {
        $this->assertField($name);
        $this->assertName($name, $component);
        $this->formComponents[$name] = $component;
    }

    public function replaceTable(string $name, Column $column): void
    {
        $this->assertField($name);
        $this->assertName($name, $column);
        $this->tableColumns[$name] = $column;
    }

    public function replaceRelationForm(string $name, Select $component): void
    {
        if ($this->entity->relation($name) === null) {
            throw new IncompleteProjection("Relation [{$name}] is not defined on [{$this->entity->name()}].");
        }

        $this->relationComponents[$name] = $component;
    }

    public function applyForm(Schema $schema): Schema
    {
        return $schema->components($this->formLayout());
    }

    public function applyTable(FilamentTable $table): FilamentTable
    {
        return $table->columns(array_values($this->tableColumns));
    }

    /**
     * @return list<Component|Section>
     */
    private function formLayout(): array
    {
        /** @var array<string, list<Component>> $sections */
        $sections = [];
        /** @var list<string> $order */
        $order = [];
        /** @var list<Component> $layout */
        $layout = [];

        foreach ($this->formComponents as $name => $component) {
            $section = $this->entity->field($name)?->attributes()->section();

            if ($section === null) {
                $layout[] = $component;

                continue;
            }

            if (! isset($sections[$section])) {
                $order[] = $section;
                $sections[$section] = [];
            }

            $sections[$section][] = $component;
        }

        foreach ($order as $section) {
            $layout[] = Section::make($section)->components($sections[$section]);
        }

        foreach ($this->relationComponents as $component) {
            $layout[] = $component;
        }

        return $layout;
    }

    private function assertField(string $name): void
    {
        if (! isset($this->formComponents[$name])) {
            throw new IncompleteProjection("Field [{$name}] is not defined on [{$this->entity->name()}].");
        }
    }

    private function assertName(string $name, object $component): void
    {
        if (! method_exists($component, 'getName')) {
            return;
        }

        $componentName = $component->getName();

        if ($componentName !== $name) {
            throw new IncompleteProjection("Replacement for [{$name}] must use the field name [{$name}].");
        }
    }
}
