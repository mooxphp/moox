<?php

namespace Moox\Core\Base;

use Filament\Widgets\TableWidget;

if (config('core.use_advanced_tables') === true && trait_exists('\Archilex\AdvancedTables\AdvancedTables')) {
    class BaseWidget extends TableWidget
    {
        use \Archilex\AdvancedTables\AdvancedTables;
    }
} else {
    class BaseWidget extends TableWidget
    {
        //
    }
}
