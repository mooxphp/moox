<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

final class FieldAttributes
{
    private function __construct(
        private readonly ?string $label,
        private readonly ?string $description,
        private readonly ?string $section,
        private readonly bool $required,
        private readonly bool $nullable,
        private readonly OptionalValue $default,
        private readonly bool $unique,
        private readonly UniqueScope $uniqueScope,
        private readonly bool $index,
        private readonly bool $immutable,
        private readonly bool $systemManaged,
        private readonly OptionalValue $generator,
        private readonly ?string $role,
    ) {
    }

    public static function make(): self
    {
        return new self(
            label: null,
            description: null,
            section: null,
            required: false,
            nullable: false,
            default: OptionalValue::absent(),
            unique: false,
            uniqueScope: UniqueScope::global(),
            index: false,
            immutable: false,
            systemManaged: false,
            generator: OptionalValue::absent(),
            role: null,
        );
    }

    public function label(): ?string
    {
        return $this->label;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function section(): ?string
    {
        return $this->section;
    }

    public function required(): bool
    {
        return $this->required;
    }

    public function nullable(): bool
    {
        return $this->nullable;
    }

    public function defaultValue(): OptionalValue
    {
        return $this->default;
    }

    public function hasDefault(): bool
    {
        return $this->default->isPresent();
    }

    public function default(): mixed
    {
        return $this->default->value();
    }

    public function unique(): bool
    {
        return $this->unique;
    }

    public function uniqueScope(): UniqueScope
    {
        return $this->uniqueScope;
    }

    public function index(): bool
    {
        return $this->index;
    }

    public function immutable(): bool
    {
        return $this->immutable;
    }

    public function systemManaged(): bool
    {
        return $this->systemManaged;
    }

    public function generator(): OptionalValue
    {
        return $this->generator;
    }

    public function isGenerated(): bool
    {
        return $this->generator->isPresent();
    }

    public function role(): ?string
    {
        return $this->role;
    }

    public function withLabel(?string $label): self
    {
        return $this->cloneWith(label: $label, labelSet: true);
    }

    public function withDescription(?string $description): self
    {
        return $this->cloneWith(description: $description, descriptionSet: true);
    }

    public function withSection(?string $section): self
    {
        return $this->cloneWith(section: $section, sectionSet: true);
    }

    public function withRequired(bool $required): self
    {
        return $this->cloneWith(required: $required);
    }

    public function withNullable(bool $nullable): self
    {
        return $this->cloneWith(nullable: $nullable);
    }

    public function withDefault(OptionalValue $default): self
    {
        return $this->cloneWith(default: $default);
    }

    public function withUnique(bool $unique, UniqueScope $uniqueScope): self
    {
        return $this->cloneWith(unique: $unique, uniqueScope: $uniqueScope);
    }

    public function withIndex(bool $index): self
    {
        return $this->cloneWith(index: $index);
    }

    public function withImmutable(bool $immutable): self
    {
        return $this->cloneWith(immutable: $immutable);
    }

    public function withSystemManaged(bool $systemManaged): self
    {
        return $this->cloneWith(systemManaged: $systemManaged);
    }

    public function withGenerator(OptionalValue $generator): self
    {
        return $this->cloneWith(generator: $generator);
    }

    public function withRole(?string $role): self
    {
        return $this->cloneWith(role: $role, roleSet: true);
    }

    private function cloneWith(
        ?string $label = null,
        ?string $description = null,
        ?string $section = null,
        ?bool $required = null,
        ?bool $nullable = null,
        ?OptionalValue $default = null,
        ?bool $unique = null,
        ?UniqueScope $uniqueScope = null,
        ?bool $index = null,
        ?bool $immutable = null,
        ?bool $systemManaged = null,
        ?OptionalValue $generator = null,
        ?string $role = null,
        bool $labelSet = false,
        bool $descriptionSet = false,
        bool $sectionSet = false,
        bool $roleSet = false,
    ): self {
        return new self(
            label: $labelSet ? $label : $this->label,
            description: $descriptionSet ? $description : $this->description,
            section: $sectionSet ? $section : $this->section,
            required: $required ?? $this->required,
            nullable: $nullable ?? $this->nullable,
            default: $default ?? $this->default,
            unique: $unique ?? $this->unique,
            uniqueScope: $uniqueScope ?? $this->uniqueScope,
            index: $index ?? $this->index,
            immutable: $immutable ?? $this->immutable,
            systemManaged: $systemManaged ?? $this->systemManaged,
            generator: $generator ?? $this->generator,
            role: $roleSet ? $role : $this->role,
        );
    }
}
