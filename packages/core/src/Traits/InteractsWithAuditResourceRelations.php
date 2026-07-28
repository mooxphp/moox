<?php

declare(strict_types=1);

namespace Moox\Core\Traits;

use Moox\Audit\Support\AuditResourceRelationRegistry;

trait InteractsWithAuditResourceRelations
{
    public static function getRelations(): array
    {
        $relations = parent::getRelations();

        if (class_exists(AuditResourceRelationRegistry::class)) {
            $relations = array_merge(
                $relations,
                AuditResourceRelationRegistry::for(static::class),
            );
        }

        return $relations;
    }
}
