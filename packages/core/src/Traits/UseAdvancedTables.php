<?php

namespace Moox\Core\Traits;

trait UseAdvancedTables
{
    public function initializeUseAdvancedTables()
    {
        // @phpstan-ignore-next-line
        if (config('core.use_advanced_tables') && class_exists(\Archilex\AdvancedTables\AdvancedTables::class)) {
            // @phpstan-ignore-next-line
            foreach (get_class_methods(\Archilex\AdvancedTables\AdvancedTables::class) as $method) {
                // @phpstan-ignore-next-line
                $this->$method = \Closure::fromCallable([\Archilex\AdvancedTables\AdvancedTables::class, $method])->bindTo($this, self::class);
            }
        }
    }
}
