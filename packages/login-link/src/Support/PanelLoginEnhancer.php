<?php

namespace Moox\LoginLink\Support;

use Filament\Auth\Pages\Login as FilamentLogin;
use Moox\LoginLink\Concerns\InteractsWithLoginLinks;

/**
 * Extends the panel's configured login class with InteractsWithLoginLinks (hint on the login field).
 */
final class PanelLoginEnhancer
{
    /** @var array<string, string> */
    private static array $cache = [];

    public static function resolve(?string $loginClass): string
    {
        $baseLoginClass = $loginClass ?? FilamentLogin::class;

        if (isset(self::$cache[$baseLoginClass])) {
            return self::$cache[$baseLoginClass];
        }

        if (! class_exists($baseLoginClass)) {
            return self::$cache[$baseLoginClass] = $baseLoginClass;
        }

        if (in_array(InteractsWithLoginLinks::class, class_uses_recursive($baseLoginClass), true)) {
            return self::$cache[$baseLoginClass] = $baseLoginClass;
        }

        $enhancedFqcn = self::enhancedClassName($baseLoginClass);

        if (! class_exists($enhancedFqcn, false)) {
            self::defineClass($enhancedFqcn, $baseLoginClass);
        }

        return self::$cache[$baseLoginClass] = $enhancedFqcn;
    }

    private static function enhancedClassName(string $baseLoginClass): string
    {
        return 'Moox\\LoginLink\\Support\\Enhanced\\Enhanced_'.hash('xxh128', $baseLoginClass);
    }

    private static function defineClass(string $enhancedFqcn, string $baseLoginClass): void
    {
        $namespace = 'Moox\\LoginLink\\Support\\Enhanced';
        $shortName = substr($enhancedFqcn, strlen($namespace) + 1);
        $extends = ltrim($baseLoginClass, '\\');

        $overrideMethods = '';

        if (method_exists($baseLoginClass, 'getLoginFormComponent')) {
            $overrideMethods = <<<'PHP'

    protected function getLoginFormComponent(): \Filament\Schemas\Components\Component
    {
        return $this->configureLoginFormWithMagicLink(parent::getLoginFormComponent());
    }
PHP;
        } elseif (method_exists($baseLoginClass, 'getEmailFormComponent')) {
            $overrideMethods = <<<'PHP'

    protected function getEmailFormComponent(): \Filament\Forms\Components\TextInput
    {
        /** @var \Filament\Forms\Components\TextInput $email */
        $email = parent::getEmailFormComponent();

        return $this->configureLoginFormWithMagicLink($email);
    }
PHP;
        }

        $code = <<<PHP
namespace {$namespace};

class {$shortName} extends \\{$extends}
{
    use \\Moox\\LoginLink\\Concerns\\InteractsWithLoginLinks;
{$overrideMethods}
}
PHP;

        eval($code);
    }
}
