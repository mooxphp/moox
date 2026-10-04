<?php

declare(strict_types=1);

namespace Moox\Definition\Resolution;

use Moox\Definition\Definitions\Relation;
use Moox\Definition\Projection\StorageColumn;

final class ResolvedRelation
{
    public function __construct(
        private readonly Relation $relation,
        private readonly FieldOrigin $origin,
        private readonly ?StorageColumn $localColumn,
    ) {
    }

    public function relation(): Relation
    {
        return $this->relation;
    }

    public function origin(): FieldOrigin
    {
        return $this->origin;
    }

    public function localColumn(): ?StorageColumn
    {
        return $this->localColumn;
    }
}
