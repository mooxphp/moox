<?php

namespace Moox\Core\Base;

use Filament\Resources\Resource;

if (config('core.use_advanced_tables') === true && trait_exists('\Archilex\AdvancedTables\AdvancedTables')) {
    class BaseResource extends Resource
    {
        use \Archilex\AdvancedTables\AdvancedTables;
    }
} else {
    class BaseResource extends Resource
    {
        //
    }
}
