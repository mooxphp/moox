<?php

declare(strict_types=1);

namespace Moox\Core\Installer\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FilamentPanelStubPublisher
{
    public const STUB_FILENAME = 'PanelProvider.php.stub';

    /**
     * @return array<string, string>
     */
    public function replacementsForAppPanel(string $panelId, ?string $className = null): array
    {
        $panelId = $this->sanitizePanelId($panelId);
        $className ??= Str::studly($panelId).'PanelProvider';

        return $this->baseReplacements(
            namespace: 'App\\Providers\\Filament',
            class: $className,
            appNamespace: 'App',
            panelId: $this->panelRouteKey($panelId),
            panelPath: $this->panelRouteKey($panelId),
            isDefault: $this->panelRouteKey($panelId) === 'admin',
        );
    }

    /**
     * @return array<string, string>
     */
    public function replacementsForPackagePanel(string $panelId, string $packageNamespace): array
    {
        $panelId = $this->sanitizePanelId($panelId);
        $className = Str::studly($panelId).'PanelProvider';

        return $this->baseReplacements(
            namespace: $packageNamespace,
            class: $className,
            appNamespace: 'App',
            panelId: $this->panelRouteKey($panelId),
            panelPath: $this->panelRouteKey($panelId),
            isDefault: $this->panelRouteKey($panelId) === 'admin',
        );
    }

    /**
     * @param  array<string, string>  $replacements
     */
    public function render(array $replacements): string
    {
        $stub = File::get($this->stubPath());

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub,
        );
    }

    public function publishToApp(string $panelId, bool $force = false): string
    {
        $panelId = $this->sanitizePanelId($panelId);

        if ($panelId === '') {
            throw new InvalidArgumentException('Invalid panel name.');
        }

        $className = Str::studly($panelId).'PanelProvider';
        $targetDir = app_path('Providers/Filament');
        $targetPath = $targetDir.'/'.$className.'.php';

        if (File::exists($targetPath) && ! $force) {
            return $targetPath;
        }

        File::ensureDirectoryExists($targetDir);

        $content = $this->render($this->replacementsForAppPanel($panelId, $className));
        File::put($targetPath, $content);

        $this->publishCorePublicAssets();

        return $targetPath;
    }

    public function publishCorePublicAssets(): void
    {
        if (! app()->runningInConsole()) {
            return;
        }

        Artisan::call('vendor:publish', [
            '--tag' => 'core-assets',
            '--force' => true,
        ]);
    }

    public function packagePanelPath(string $panelId): string
    {
        $panelId = $this->sanitizePanelId($panelId);

        return dirname(__DIR__, 2).'/Panels/'.Str::studly($panelId).'PanelProvider.php';
    }

    public function ensurePackagePanel(string $panelId, string $packageNamespace): string
    {
        $panelId = $this->sanitizePanelId($panelId);
        $path = $this->packagePanelPath($panelId);

        if (File::exists($path)) {
            return $path;
        }

        File::ensureDirectoryExists(dirname($path));

        $className = Str::studly($panelId).'PanelProvider';
        $content = $this->render($this->replacementsForPackagePanel($panelId, $packageNamespace));
        File::put($path, $content);

        return $path;
    }

    public function registerInBootstrapProviders(string $providerClass): void
    {
        $bootstrapProvidersPath = base_path('bootstrap/providers.php');

        if (! File::exists($bootstrapProvidersPath)) {
            return;
        }

        $content = File::get($bootstrapProvidersPath);

        if (preg_match('/^\s*'.preg_quote($providerClass, '/').'::class,?\s*$/m', $content)) {
            return;
        }

        if (! preg_match('/return\s*\[(.*?)\];/s', $content, $matches)) {
            return;
        }

        $inner = trim($matches[1]);

        if ($inner !== '' && ! str_ends_with(trim($inner), ',')) {
            $inner .= ',';
        }

        $inner .= "\n    {$providerClass}::class,";

        $newContent = preg_replace(
            '/return\s*\[.*?\];/s',
            "return [\n{$inner}\n];",
            $content,
            1,
        );

        File::put($bootstrapProvidersPath, $newContent);
    }

    public function stubPath(): string
    {
        return dirname(__DIR__, 3).'/stubs/filament/'.self::STUB_FILENAME;
    }

    public function sanitizePanelId(string $panelId): string
    {
        return preg_replace('/[^a-zA-Z0-9-]/', '', $panelId) ?? '';
    }

    public function panelRouteKey(string $panelId): string
    {
        return Str::lower($this->sanitizePanelId($panelId));
    }

    /**
     * @return array<string, string>
     */
    protected function baseReplacements(
        string $namespace,
        string $class,
        string $appNamespace,
        string $panelId,
        string $panelPath,
        bool $isDefault,
    ): array {
        return [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $class,
            '{{ app_namespace }}' => $appNamespace,
            '{{ panel_id }}' => $panelId,
            '{{ panel_path }}' => $panelPath,
            '{{ primary_color }}' => 'Color::Violet',
            '{{ default_panel }}' => $isDefault ? "->default()\n            " : '',
        ];
    }
}
