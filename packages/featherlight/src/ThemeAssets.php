<?php

namespace Moox\Featherlight;

use Illuminate\Support\Facades\File;

class ThemeAssets
{
    protected ?array $manifest = null;

    /**
     * Load the Vite manifest file
     */
    protected function loadManifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $manifestPath = dirname(__DIR__).'/resources/dist/.vite/manifest.json';

        if (File::exists($manifestPath)) {
            $this->manifest = json_decode(File::get($manifestPath), true);

            return $this->manifest;
        }

        return $this->manifest = [];
    }

    /**
     * Get the asset filename from the manifest
     */
    protected function getAssetFromManifest(string $entrypoint): ?string
    {
        $manifest = $this->loadManifest();

        if (isset($manifest[$entrypoint]['file'])) {
            return basename($manifest[$entrypoint]['file']);
        }

        if (isset($manifest[$entrypoint]['css']) && count($manifest[$entrypoint]['css']) > 0) {
            return basename($manifest[$entrypoint]['css'][0]);
        }

        return null;
    }

    /**
     * Get the URL for a theme asset
     */
    public function url(string $path): string
    {
        return url('/featherlight/assets/'.$path);
    }

    /**
     * Get the CSS tag for the theme
     */
    public function css(): string
    {
        $cssFile = $this->getAssetFromManifest('resources/src/app.css');

        if (! $cssFile) {
            $cssFile = 'app.css';
        }

        return '<link href="'.$this->url($cssFile).'" rel="stylesheet">';
    }

    /**
     * Get the JS tag for the theme
     */
    public function js(): string
    {
        $jsFile = $this->getAssetFromManifest('resources/src/app.js');

        if (! $jsFile) {
            $jsFile = 'app.js';
        }

        return '<script src="'.$this->url($jsFile).'" defer></script>';
    }

    /**
     * Get both CSS and JS tags
     */
    public function tags(): string
    {
        return $this->css().PHP_EOL.$this->js();
    }
}
