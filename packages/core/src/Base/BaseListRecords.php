<?php

namespace Moox\Core\Base;

use Filament\Resources\Pages\ListRecords;

if (config('core.use_advanced_tables') === true && trait_exists('\Archilex\AdvancedTables\AdvancedTables')) {
    class BaseListRecords extends ListRecords
    {
        use \Archilex\AdvancedTables\AdvancedTables;

        public bool $useAdvancedTables = true;
    }
} else {
    class BaseListRecords extends ListRecords
    {
        public bool $useAdvancedTables = false;
    }
}
