<?php

declare(strict_types=1);

namespace Moox\Core\Relations\Enums;

enum RelationKind: string
{
    case BelongsToMany = 'belongs_to_many';
    case MorphPivot = 'morph_pivot';
    case PivotHasMany = 'pivot_has_many';
    case HasMany = 'has_many';
    case HasOne = 'has_one';
    case BelongsTo = 'belongs_to';
}
