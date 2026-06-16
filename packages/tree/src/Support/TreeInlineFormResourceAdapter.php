<?php

declare(strict_types=1);

namespace Moox\Tree\Support;

use Moox\Tree\Filament\Concerns\ProvidesInlineResourceFormActions;
use ReflectionClass;
use RuntimeException;

/**
 * Wraps a forwarded Filament resource with {@see ProvidesInlineResourceFormActions}
 * for inline inspector forms — consumers do not apply the trait themselves.
 */
final class TreeInlineFormResourceAdapter
{
    private const GENERATED_NAMESPACE = 'Moox\\Tree\\Filament\\Resources\\Generated';

    private const GENERATED_DIRECTORY = __DIR__.'/../Filament/Resources/Generated';

    /** @var array<class-string, class-string> */
    private static array $resolved = [];

    /**
     * @param  class-string  $resourceClass
     * @return class-string
     */
    public static function resolve(string $resourceClass): string
    {
        if (isset(self::$resolved[$resourceClass])) {
            return self::$resolved[$resourceClass];
        }

        if ((new ReflectionClass($resourceClass))->isFinal()) {
            throw new RuntimeException("Tree inline form adapter cannot extend final resource [{$resourceClass}]. Remove the `final` keyword from the forwarded resource class.");
        }

        $generatedClass = self::generatedClassName($resourceClass);

        if (! class_exists($generatedClass)) {
            self::writeGeneratedClass($resourceClass, $generatedClass);
        }

        if (! is_subclass_of($generatedClass, $resourceClass)) {
            throw new RuntimeException("Generated tree inline form class [{$generatedClass}] must extend [{$resourceClass}].");
        }

        return self::$resolved[$resourceClass] = $generatedClass;
    }

    /**
     * @param  class-string  $resourceClass
     * @return class-string
     */
    private static function generatedClassName(string $resourceClass): string
    {
        $basename = class_basename($resourceClass);
        $hash = substr(md5($resourceClass), 0, 16);

        return self::GENERATED_NAMESPACE.'\\TreeInlineForm_'.$basename.'_'.$hash;
    }

    /**
     * @param  class-string  $resourceClass
     * @param  class-string  $generatedClass
     */
    private static function writeGeneratedClass(string $resourceClass, string $generatedClass): void
    {
        $shortClass = class_basename($generatedClass);
        $traitClass = ProvidesInlineResourceFormActions::class;

        if (! is_dir(self::GENERATED_DIRECTORY)) {
            mkdir(self::GENERATED_DIRECTORY, 0755, true);
        }

        $path = self::GENERATED_DIRECTORY.'/'.$shortClass.'.php';

        $contents = '<?php'.PHP_EOL.PHP_EOL
            .'declare(strict_types=1);'.PHP_EOL.PHP_EOL
            .'namespace Moox\Tree\Filament\Resources\Generated;'.PHP_EOL.PHP_EOL
            ."class {$shortClass} extends \\{$resourceClass}".PHP_EOL
            .'{'.PHP_EOL
            ."    use \\{$traitClass};".PHP_EOL
            .'}'.PHP_EOL;

        file_put_contents($path, $contents);
    }
}
