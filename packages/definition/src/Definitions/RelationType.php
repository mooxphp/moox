<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

enum RelationType: string
{
    case BelongsTo = 'belongsTo';
    case HasOne = 'hasOne';
    case HasMany = 'hasMany';
    case BelongsToMany = 'belongsToMany';
    case HasOneThrough = 'hasOneThrough';
    case HasManyThrough = 'hasManyThrough';
    case MorphTo = 'morphTo';
    case MorphOne = 'morphOne';
    case MorphMany = 'morphMany';
    case MorphToMany = 'morphToMany';
    case MorphedByMany = 'morphedByMany';
}
