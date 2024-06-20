<?php

namespace Moox\Core\Base;

use Filament\Resources\RelationManagers\RelationManager;

if (config('core.use_advanced_tables') === true && trait_exists('\Archilex\AdvancedTables\AdvancedTables')) {
    class BaseRelationManager extends RelationManager
    {
        use \Archilex\AdvancedTables\AdvancedTables;

        public bool $useAdvancedTables = true;
    }
} else {
    class BaseRelationManager extends RelationManager
    {
        public bool $useAdvancedTables = false;
    }
}
