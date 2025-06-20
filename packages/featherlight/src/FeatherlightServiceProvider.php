<?php

declare(strict_types=1);

namespace Moox\Featherlight;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class FeatherlightServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('featherlight')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Featherlight')
            ->released(true)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                'building new Moox packages, not used as installed package',
            ])
            ->alternatePackages([
                'moox/builder', // optional alternative package (e.g. moox/post)
            ])
            ->templateFor([
                'we do not know yet',
            ])
            ->templateReplace([
                'Featherlight' => '%%PackageName%%',
                'featherlight' => '%%PackageSlug%%',
                'This is my package featherlight' => '%%Description%%',
                'building new Moox packages, not used as installed package' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Featherlight' => '%%PackageName%%',
                'featherlight' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateRemove([
                'build.php',
            ]);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/featherlight.php', 'featherlight');

        // Register theme assets singleton
        $this->app->singleton(ThemeAssets::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'featherlight');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Route::get('/featherlight/assets/{path}', function ($path) {
            $assetPath = __DIR__.'/../resources/dist/assets/'.$path;

            if (! File::exists($assetPath)) {
                abort(404, "Theme asset not found: {$path}");
            }

            $contentType = File::mimeType($assetPath);
            $fileContent = File::get($assetPath);

            $extension = pathinfo($assetPath, PATHINFO_EXTENSION);
            if (strtolower($extension) === 'css') {
                $contentType = 'text/css';
            }

            // Cache for 1 year (far future expires)
            $response = response($fileContent)
                ->header('Content-Type', $contentType)
                ->header('Cache-Control', 'public, max-age=31536000');

            return $response;
        })->where('path', '.*');

        Blade::directive('featherlightAssets', function () {
            return '<?php echo app(\\Moox\\Featherlight\\ThemeAssets::class)->tags(); ?>';
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/featherlight.php' => config_path('featherlight.php'),
                __DIR__.'/../resources/views' => resource_path('views/vendor/featherlight'),
            ], 'featherlight');
        }
    }
}
