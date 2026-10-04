<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

final class RelationKeys
{
    public function __construct(
        private readonly ?string $localKey = null,
        private readonly ?string $foreignKey = null,
        private readonly ?string $ownerKey = null,
        private readonly ?string $relatedKey = null,
        private readonly ?string $throughKey = null,
        private readonly ?string $morphName = null,
        private readonly ?string $morphType = null,
        private readonly ?string $morphId = null,
    ) {
    }

    public function localKey(): ?string
    {
        return $this->localKey;
    }

    public function foreignKey(): ?string
    {
        return $this->foreignKey;
    }

    public function ownerKey(): ?string
    {
        return $this->ownerKey;
    }

    public function relatedKey(): ?string
    {
        return $this->relatedKey;
    }

    public function throughKey(): ?string
    {
        return $this->throughKey;
    }

    public function morphName(): ?string
    {
        return $this->morphName;
    }

    public function morphType(): ?string
    {
        return $this->morphType;
    }

    public function morphId(): ?string
    {
        return $this->morphId;
    }
}
