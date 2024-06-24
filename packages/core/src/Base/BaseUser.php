<?php

namespace Moox\Core\Base;

use Illuminate\Foundation\Auth\User as Authenticatable;

if (config('core.use_advanced_tables') === true && trait_exists('\Archilex\AdvancedTables\AdvancedTables')) {
    class BaseUser extends Authenticatable
    {
        use \Archilex\AdvancedTables\Concerns\HasViews;

        public bool $useAdvancedViews = true;
    }
} else {
    class BaseUser extends Authenticatable
    {
        public bool $useAdvancedViews = false;
    }
}
