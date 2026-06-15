<?php

declare(strict_types=1);

namespace Moox\Audit\Filament\Concerns;

use Moox\Audit\Support\AuditResourceRelationRegistry;

trait InteractsWithAuditResourceRelations
{
    /**
     * @return array<class-string|\Filament\Resources\RelationManagers\RelationGroup|\Filament\Resources\RelationManagers\RelationManagerConfiguration>
     */
    public static function getRelations(): array
    {
        return array_merge(
            parent::getRelations(),
            AuditResourceRelationRegistry::for(static::class),
        );
    }
}
