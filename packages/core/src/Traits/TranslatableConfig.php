<?php

namespace Moox\Core\Traits;

trait TranslatableConfig
{
    /**
     * Translate config values marked with the 'trans//' pattern.
     */
    protected function translateConfig(array $config): array
    {
        foreach ($config as &$value) {
            if (is_array($value)) {
                $value = $this->translateConfig($value);

                continue;
            }

            if (is_string($value) && str_starts_with($value, 'trans//')) {
                $translationKey = substr($value, 7);
                $translated = trans($translationKey);
                if ($translated !== $translationKey) {
                    $value = $translated;
                }
            }
        }

        return $config;
    }
}
