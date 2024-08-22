<?php

namespace Moox\Core\Traits;

use Illuminate\Support\Facades\Lang;

trait TranslatableConfig
{
    /**
     * Translate config values marked with the 'trans//' pattern.
     */
    protected function translateConfig(array $config): array
    {

        array_walk_recursive($config, function (&$value) {
            if (is_string($value) && str_starts_with($value, 'trans//')) {
                $key = str_replace('trans//', '', $value);
                $translation = Lang::get($key);
                $value = $translation !== $key ? $translation : $key;
            }
        });

        return $config;
    }
}
