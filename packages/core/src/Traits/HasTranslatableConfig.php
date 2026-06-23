<?php

namespace Moox\Core\Traits;

use Illuminate\Translation\Translator;

trait HasTranslatableConfig
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

    /**
     * Clear cached translation groups after {@see translateConfig()}.
     *
     * {@see translateConfig()} may resolve keys before all package namespaces
     * are registered, leaving empty groups cached permanently.
     */
    protected function resetTranslatorLoadedGroups(): void
    {
        $translator = $this->app->make('translator');

        if (! $translator instanceof Translator) {
            return;
        }

        $reflection = new \ReflectionClass($translator);
        $property = $reflection->getProperty('loaded');
        $property->setAccessible(true);
        $property->setValue($translator, []);
    }
}
