<?php

namespace Moox\Prompts\Support;

use Spatie\LaravelPackageTools\PackageServiceProvider;

class WebCommandRunner
{
    /**
     * Ensure publishable resources are registered in web context.
     * This fixes the issue where Spatie Package Tools only registers
     * publishable resources when runningInConsole() is true.
     */
    public static function ensurePublishableResourcesRegistered(): void
    {
        $app = app();
        $loadedProviders = $app->getLoadedProviders();

        foreach ($loadedProviders as $providerClass => $loaded) {
            if (! $loaded || ! class_exists($providerClass)) {
                continue;
            }

            try {
                $provider = static::getProviderInstance($app, $providerClass);

                if (! $provider instanceof PackageServiceProvider) {
                    continue;
                }

                $package = static::getPackage($provider);
                if (! $package) {
                    continue;
                }

                static::registerAllPublishes($provider, $package);
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    protected static function getProviderInstance($app, string $providerClass)
    {
        $reflection = new \ReflectionClass($app);
        $property = $reflection->getProperty('serviceProviders');
        $property->setAccessible(true);
        $providers = $property->getValue($app);

        return $providers[$providerClass] ?? null;
    }

    protected static function getPackage(PackageServiceProvider $provider)
    {
        $reflection = new \ReflectionClass($provider);

        if (! $reflection->hasProperty('package')) {
            return null;
        }

        $property = $reflection->getProperty('package');
        $property->setAccessible(true);

        if (! $property->isInitialized($provider)) {
            return null;
        }

        return $property->getValue($provider);
    }

    protected static function registerAllPublishes(PackageServiceProvider $provider, $package): void
    {
        static::registerConfigPublishes($provider, $package);
        static::registerMigrationPublishes($provider, $package);
        static::registerViewPublishes($provider, $package);
        static::registerTranslationPublishes($provider, $package);
        static::registerAssetPublishes($provider, $package);
    }

    protected static function registerConfigPublishes(PackageServiceProvider $provider, $package): void
    {
        $configFileNames = static::getPackageProperty($package, 'configFileNames');
        if (empty($configFileNames)) {
            return;
        }

        $basePathMethod = static::getPackageMethod($package, 'basePath');
        $shortName = static::invokePackageMethod($package, 'shortName');

        foreach ($configFileNames as $configFileName) {
            $vendorConfig = $basePathMethod("/../config/{$configFileName}.php");

            if (! is_file($vendorConfig)) {
                $vendorConfig = $basePathMethod("/../config/{$configFileName}.php.stub");
                if (! is_file($vendorConfig)) {
                    continue;
                }
            }

            static::callPublishes($provider, [
                $vendorConfig => config_path("{$configFileName}.php"),
            ], "{$shortName}-config");
        }
    }

    protected static function registerMigrationPublishes(PackageServiceProvider $provider, $package): void
    {
        $migrationFileNames = static::getPackageProperty($package, 'migrationFileNames');
        if (empty($migrationFileNames)) {
            return;
        }

        $basePathMethod = static::getPackageMethod($package, 'basePath');
        $shortName = static::invokePackageMethod($package, 'shortName');

        foreach ($migrationFileNames as $migrationFileName) {
            $vendorMigration = $basePathMethod("/../database/migrations/{$migrationFileName}.php");
            if (! is_file($vendorMigration)) {
                $vendorMigration = $basePathMethod("/../database/migrations/{$migrationFileName}.php.stub");
                if (! is_file($vendorMigration)) {
                    continue;
                }
            }

            static::callPublishes($provider, [
                $vendorMigration => database_path("migrations/{$migrationFileName}.php"),
            ], "{$shortName}-migrations");
        }
    }

    protected static function registerViewPublishes(PackageServiceProvider $provider, $package): void
    {
        if (! static::getPackageProperty($package, 'hasViews')) {
            return;
        }

        $basePathMethod = static::getPackageMethod($package, 'basePath');
        $shortName = static::invokePackageMethod($package, 'shortName');
        $viewsPath = $basePathMethod('/../resources/views');

        if (! is_dir($viewsPath)) {
            return;
        }

        static::callPublishes($provider, [
            $viewsPath => base_path("resources/views/vendor/{$shortName}"),
        ], "{$shortName}-views");
    }

    protected static function registerTranslationPublishes(PackageServiceProvider $provider, $package): void
    {
        if (! static::getPackageProperty($package, 'hasTranslations')) {
            return;
        }

        $basePathMethod = static::getPackageMethod($package, 'basePath');
        $shortName = static::invokePackageMethod($package, 'shortName');
        $vendorTranslations = $basePathMethod('/../resources/lang');

        if (! is_dir($vendorTranslations)) {
            return;
        }

        $appTranslations = function_exists('lang_path')
            ? lang_path("vendor/{$shortName}")
            : resource_path("lang/vendor/{$shortName}");

        static::callPublishes($provider, [
            $vendorTranslations => $appTranslations,
        ], "{$shortName}-translations");
    }

    protected static function registerAssetPublishes(PackageServiceProvider $provider, $package): void
    {
        if (! static::getPackageProperty($package, 'hasAssets')) {
            return;
        }

        $basePathMethod = static::getPackageMethod($package, 'basePath');
        $shortName = static::invokePackageMethod($package, 'shortName');
        $vendorAssets = $basePathMethod('/../resources/dist');

        if (! is_dir($vendorAssets)) {
            return;
        }

        static::callPublishes($provider, [
            $vendorAssets => public_path("vendor/{$shortName}"),
        ], "{$shortName}-assets");
    }

    protected static function getPackageProperty($package, string $property)
    {
        $reflection = new \ReflectionClass($package);

        if (! $reflection->hasProperty($property)) {
            return null;
        }

        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);

        return $prop->getValue($package);
    }

    protected static function getPackageMethod($package, string $method): callable
    {
        $reflection = new \ReflectionClass($package);
        $methodRef = $reflection->getMethod($method);
        $methodRef->setAccessible(true);

        return fn (...$args) => $methodRef->invoke($package, ...$args);
    }

    protected static function invokePackageMethod($package, string $method)
    {
        $reflection = new \ReflectionClass($package);
        $methodRef = $reflection->getMethod($method);
        $methodRef->setAccessible(true);

        return $methodRef->invoke($package);
    }

    protected static function callPublishes(PackageServiceProvider $provider, array $paths, ?string $group = null): void
    {
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('publishes');
        $method->setAccessible(true);
        $method->invoke($provider, $paths, $group);
    }
}
