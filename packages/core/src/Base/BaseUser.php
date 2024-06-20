<?php

namespace Moox\Core\Base;

use Filament\Models\Contracts\FilamentUser;

if (config('core.use_advanced_tables') === true && trait_exists('\Archilex\AdvancedTables\AdvancedTables')) {
    class BaseUser extends FilamentUser
    {
        use \Archilex\AdvancedTables\Concerns\HasViews;

        public bool $useAdvancedViews = true;
    }
} else {
    class BaseUser extends FilamentUser
    {
        public bool $useAdvancedViews = false;
    }
}
