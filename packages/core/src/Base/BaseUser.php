<?php

namespace Moox\Core\Base;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

if (config('core.use_advanced_tables') === true && trait_exists('\Archilex\AdvancedTables\AdvancedTables')) {
    class BaseUser implements FilamentUser
    {
        use \Archilex\AdvancedTables\Concerns\HasViews;

        public bool $useAdvancedViews = true;

        public function canAccessPanel(Panel $panel): bool
        {
            return true;
        }
    }
} else {
    class BaseUser implements FilamentUser
    {
        public bool $useAdvancedViews = false;

        public function canAccessPanel(Panel $panel): bool
        {
            return true;
        }
    }
}
