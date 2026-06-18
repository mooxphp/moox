<?php

declare(strict_types=1);

namespace Moox\Audit\Filament\Concerns;

use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Moox\Audit\Support\AuditResourceRelationRegistry;

trait InteractsWithAuditResourceRelations
{
    /**
     * @return array<class-string|RelationGroup|RelationManagerConfiguration>
     */
    public static function getRelations(): array
    {
        return array_merge(
            parent::getRelations(),
            AuditResourceRelationRegistry::for(static::class),
        );
    }
}
