<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Pages;

use Illuminate\Support\Str;
use Livewire\Livewire;
use Moox\Tree\Config\TreeIndexConfigurationRegistry;
use Moox\Tree\Filament\Concerns\RendersAsTreeIndexCreateInspector;
use Moox\Tree\Support\TreeIndexResourcePages;

final class TreeIndexCreateInspectorPageFactory
{
    /** @var array<string, class-string> */
    private static array $resolved = [];

    /**
     * @return class-string
     */
    public static function resolve(string $configurationKey): string
    {
        if (isset(self::$resolved[$configurationKey])) {
            return self::$resolved[$configurationKey];
        }

        $configuration = TreeIndexConfigurationRegistry::get($configurationKey);

        $explicit = $configuration->getInspectorCreatePageClass();

        if ($explicit !== null) {
            return self::$resolved[$configurationKey] = $explicit;
        }

        $createPageClass = TreeIndexResourcePages::resolveCreatePageClass($configuration);

        if ($createPageClass === null) {
            throw new \LogicException(
                "Tree index configuration [{$configurationKey}] has no create page on the source resource.",
            );
        }

        $hash = substr(hash('xxh128', $configurationKey.'|'.$createPageClass), 0, 16);
        $className = "Moox\\Tree\\Filament\\Pages\\Generated\\TreeCreateInspector_{$hash}";

        if (! class_exists($className, false)) {
            self::declareClass($className, $createPageClass);
        }

        return self::$resolved[$configurationKey] = $className;
    }

    /**
     * @param  class-string  $className
     * @param  class-string  $createPageClass
     */
    private static function declareClass(string $className, string $createPageClass): void
    {
        $lastSeparator = strrpos($className, '\\');
        $namespace = substr($className, 0, $lastSeparator);
        $shortName = substr($className, $lastSeparator + 1);
        $parentClass = ltrim($createPageClass, '\\');
        $traitClass = ltrim(RendersAsTreeIndexCreateInspector::class, '\\');

        $code = <<<PHP
namespace {$namespace};

class {$shortName} extends \\{$parentClass}
{
    use \\{$traitClass};
}
PHP;

        eval($code);

        self::registerWithLivewire($className);
    }

    /**
     * @param  class-string  $className
     */
    private static function registerWithLivewire(string $className): void
    {
        if (! function_exists('app') || ! app()->bound('livewire')) {
            return;
        }

        $normalized = str_replace(['/', '\\'], '.', ltrim($className, '\\'));

        $livewireName = collect(explode('.', $normalized))
            ->map(fn (string $segment): string => Str::kebab($segment))
            ->implode('.');

        Livewire::component($livewireName, $className);
    }
}
