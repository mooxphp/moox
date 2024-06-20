<?php

namespace Moox\Core\Base;

use Filament\Resources\RelationManagers\RelationManager;

if (config('core.use_advanced_tables') === true && trait_exists('\Archilex\AdvancedTables\AdvancedTables')) {
    class BaseRelationManager extends RelationManager
    {
        // @phpstan-ignore-next-line
        use \Archilex\AdvancedTables\AdvancedTables;
    }
} else {
    class BaseRelationManager extends RelationManager {}
}
