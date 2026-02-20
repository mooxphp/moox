<?php

namespace Moox\Core\Installer\Installers;

use Filament\Support\Commands\Concerns\CanGeneratePanels;
use Filament\Support\Commands\Concerns\CanManipulateFiles;
use Illuminate\Support\Facades\File;
use Moox\Core\Installer\AbstractAssetInstaller;
use Moox\Core\Installer\Contracts\PanelAwareInstallerInterface;
use Symfony\Component\Console\Input\StringInput;

use function Moox\Prompts\confirm;
use function Moox\Prompts\info;
use function Moox\Prompts\multiselect;
use function Moox\Prompts\note;
use function Moox\Prompts\select;
use function Moox\Prompts\text;
use function Moox\Prompts\warning;

/**
 * Installer for Filament plugins.
 */
class PluginInstaller extends AbstractAssetInstaller implements PanelAwareInstallerInterface
{
    use CanGeneratePanels;
    use CanManipulateFiles;

    protected ?string $panelPath = null;

    protected $panelSelector = null;

    public function getType(): string
    {
        return 'plugins';
    }

    public function getLabel(): string
    {
        return 'Plugins';
    }

    protected function getMooxInfoKey(): string
    {
        return 'plugins';
    }

    protected function getDefaultConfig(): array
    {
        return array_merge(parent::getDefaultConfig(), [
            'priority' => 100,
            'allow_multiple_panels' => true,
        ]);
    }

    public function hasItemSelection(): bool
    {
        return false;
    }

    public function setPanelPath(?string $panelPath): void
    {
        $this->panelPath = $panelPath;
    }

    public function getPanelPath(): ?string
    {
        return $this->panelPath;
    }

    public function requiresPanelSelection(): bool
    {
        return true;
    }

    public function setPanelSelector($selector): self
    {
        $this->panelSelector = $selector;

        return $this;
    }

    public function checkExists(string $packageName, array $items): bool
    {
        return false;
    }

    public function install(array $assets): bool
    {
        $allPlugins = [];
        $packagePluginMap = [];

        foreach ($assets as $asset) {
            $packageName = $asset['package'];
            $pluginList = $asset['data'] ?? [];

            foreach ($pluginList as $plugin) {
                $allPlugins[$plugin] = $plugin;
                $packagePluginMap[$plugin] = $packageName;
            }
        }

        if (empty($allPlugins)) {
            note('ℹ️ No plugins found, skipping');

            return true;
        }

        $allowMultiplePanels = $this->config['allow_multiple_panels'] ?? true;

        while (true) {
            try {
                $panelPath = $this->selectOrCreatePanel();
                if (! $panelPath) {
                    note('ℹ️ No panel selected, skipping plugin installation');
                    break;
                }

                info('Selected Panel: '.basename($panelPath, '.php'));

                $pluginChoices = [];
                foreach ($allPlugins as $plugin) {
                    $displayName = basename(str_replace('\\', '/', $plugin));
                    $package = $packagePluginMap[$plugin] ?? 'unknown';
                    $pluginChoices["{$displayName} ({$package})"] = $plugin;
                }

                $selectedLabels = multiselect(
                    label: 'Select plugins to install in this panel:',
                    options: array_keys($pluginChoices),
                    default: array_keys($pluginChoices),
                    scroll: (string) min(10, count($pluginChoices)),
                    required: false
                );

                if (empty($selectedLabels)) {
                    note('No plugins selected for this panel');
                } else {
                    $selectedPlugins = array_map(
                        fn ($label) => $pluginChoices[$label],
                        $selectedLabels
                    );

                    $this->registerPluginsInPanel($selectedPlugins, $panelPath);
                }

                if (! $allowMultiplePanels || ! confirm(label: 'Install plugins in another panel?', default: false)) {
                    break;
                }
            } catch (\Exception $e) {
                warning("⚠️ Plugin installation error: {$e->getMessage()}");
                break;
            }
        }

        return true; // Always return true to continue with other installers
    }

    protected function selectOrCreatePanel(): ?string
    {
        if ($this->panelSelector) {
            return call_user_func($this->panelSelector);
        }

        $existingPanels = $this->getExistingPanels();

        if (empty($existingPanels)) {
            if (confirm(label: 'No panels found. Create a new panel?', default: true)) {
                return $this->createNewPanel();
            }

            return null;
        }

        $options = [];
        foreach ($existingPanels as $panel) {
            $displayName = basename(str_replace('\\', '/', $panel));
            $options[$panel] = $displayName;
        }
        $options['__new__'] = 'Create new panel';
        $options['__skip__'] = 'Skip';

        $selected = select(
            label: 'Which panel should be used?',
            options: $options,
            default: array_key_first($options)
        );

        if ($selected === '__skip__') {
            return null;
        }

        if ($selected === '__new__') {
            return $this->createNewPanel();
        }

        return $this->resolvePanelPath($selected);
    }

    protected function getExistingPanels(): array
    {
        $panels = [];
        $bootstrapPath = base_path('bootstrap/providers.php');

        if (File::exists($bootstrapPath)) {
            $content = File::get($bootstrapPath);
            if (preg_match_all('/([\\\\A-Za-z0-9_]+)::class/', $content, $matches)) {
                foreach ($matches[1] as $class) {
                    if (str_contains($class, 'PanelProvider')) {
                        $panels[] = $class;
                    }
                }
            }
        }

        $filamentPath = app_path('Providers/Filament');
        if (File::isDirectory($filamentPath)) {
            $files = File::files($filamentPath);
            foreach ($files as $file) {
                if (str_ends_with($file->getFilename(), 'PanelProvider.php')) {
                    $className = 'App\\Providers\\Filament\\'.basename($file->getFilename(), '.php');
                    if (! in_array($className, $panels)) {
                        $panels[] = $className;
                    }
                }
            }
        }

        return $panels;
    }

    protected function resolvePanelPath(string $panelClass): ?string
    {
        $parts = explode('\\', $panelClass);
        $className = end($parts);

        $appPath = app_path('Providers/Filament/'.$className.'.php');
        if (File::exists($appPath)) {
            return $appPath;
        }

        $composerPath = base_path('composer.json');
        if (File::exists($composerPath)) {
            $composer = json_decode(File::get($composerPath), true);
            $psr4 = $composer['autoload']['psr-4'] ?? [];

            foreach ($psr4 as $namespace => $dir) {
                if (str_starts_with($panelClass, rtrim($namespace, '\\'))) {
                    $relative = str_replace('\\', '/', substr($panelClass, strlen($namespace)));
                    $path = base_path(rtrim($dir, '/').'/'.$relative.'.php');
                    if (File::exists($path)) {
                        return $path;
                    }
                }
            }
        }

        return null;
    }

    protected function createNewPanel(): ?string
    {
        $panelName = text(
            label: 'Panel name (e.g. admin, cms):',
            default: 'admin',
            required: true
        );

        // Sanitize panel name - only allow alphanumeric and hyphens
        $panelName = preg_replace('/[^a-zA-Z0-9-]/', '', $panelName);

        if (empty($panelName)) {
            warning('Invalid panel name');

            return null;
        }

        $filamentPath = app_path('Providers/Filament');
        if (! File::isDirectory($filamentPath)) {
            File::makeDirectory($filamentPath, 0755, true);
        }

        $className = ucfirst($panelName).'PanelProvider';
        $expectedPath = $filamentPath.'/'.$className.'.php';

        try {
            // Verwende $this->command->call() wenn verfügbar (nach Prompts funktioniert das besser)
            // Das nutzt den korrekten IO-Context vom Command
            if ($this->command) {
                $exitCode = $this->command->call('make:filament-panel', [
                    'id' => $panelName,
                    '--force' => File::exists($expectedPath),
                ]);
            } else {
                // Fallback: Direkt über Application mit sauberem IO-Context
                // Das umgeht das Problem mit dem veränderten IO-Context nach Prompts
                $commandString = 'make:filament-panel '.$panelName;
                if (File::exists($expectedPath)) {
                    $commandString .= ' --force';
                }

                $input = new StringInput($commandString);
                // Wichtig: Als interaktiv markieren, damit Prompts funktionieren
                $input->setInteractive(true);
                $exitCode = app()->handleCommand($input);
            }

            if ($exitCode !== 0) {
                warning("⚠️ Panel konnte nicht erstellt werden (Exit Code: {$exitCode})");

                return null;
            }

            // Prüfen ob die Datei erstellt wurde
            if (File::exists($expectedPath)) {
                note("✅ Panel created: {$panelName}");

                return $expectedPath;
            }

            warning('Panel creation may have failed - file not found');

            return null;
        } catch (\Throwable $e) {
            warning("Could not create panel: {$e->getMessage()}");

            return null;
        }
    }

    protected function registerPluginsInPanel(array $pluginClasses, string $panelPath): void
    {
        if (empty($pluginClasses) || ! File::exists($panelPath)) {
            return;
        }

        $content = File::get($panelPath);
        $pluginsToAdd = [];

        foreach ($pluginClasses as $pluginClass) {
            if ($this->isPluginAlreadyRegistered($pluginClass, $content)) {
                note("Plugin already registered: {$pluginClass}");

                continue;
            }

            $pluginClassWithBackslash = str_starts_with($pluginClass, '\\') ? $pluginClass : '\\'.$pluginClass;
            $pluginsToAdd[] = $pluginClassWithBackslash;
        }

        if (empty($pluginsToAdd)) {
            note('All plugins are already registered');

            return;
        }

        $pluginsList = implode("::make(),\n                ", $pluginsToAdd).'::make()';
        $changed = false;

        if (preg_match('/->plugins\(\s*\[/', $content)) {
            $content = preg_replace(
                '/->plugins\(\s*\[/',
                "->plugins([\n                {$pluginsList},\n                ",
                $content,
                1
            );
            $changed = true;
        } else {
            $pluginsSection = "\n            ->plugins([\n                {$pluginsList},\n            ])";
            $lastPos = -1;
            $patternToMatch = '';
            $isArrayPattern = false;

            if (preg_match_all('/\]\s*;\s*\n\s*\}/', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $lastMatch = end($matches[0]);
                $lastPos = $lastMatch[1];
                $patternToMatch = $lastMatch[0];
                $isArrayPattern = true;
            } elseif (preg_match_all('/\)\s*;\s*\n\s*\}/', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $lastMatch = end($matches[0]);
                $lastPos = $lastMatch[1];
                $patternToMatch = $lastMatch[0];
                $isArrayPattern = false;
            }

            if ($lastPos !== -1) {
                $beforeLast = substr($content, 0, $lastPos);
                $afterLast = substr($content, $lastPos + strlen($patternToMatch));

                if (preg_match('/[\]\)]\s*;(.*)/s', $patternToMatch, $closingMatch)) {
                    $closingPart = $closingMatch[1];
                } else {
                    $closingPart = "\n    }";
                }

                $closingBracket = $isArrayPattern ? ']' : ')';
                $replacement = $closingBracket.$pluginsSection.';'.$closingPart;
                $content = $beforeLast.$replacement.$afterLast;
                $changed = true;
            } else {
                warning('Could not find plugin registration point in panel file');

                return;
            }
        }

        File::put($panelPath, $content);
        foreach ($pluginsToAdd as $plugin) {
            info("Registered plugin: {$plugin}");
        }
        info('Plugins registered in panel');
    }

    /**
     * Check if a plugin is already registered in the panel file.
     * Checks both the full namespace and the short class name (if used with 'use' statement).
     * Only considers plugins that are actually registered in the ->plugins() array.
     */
    protected function isPluginAlreadyRegistered(string $pluginClass, string $content): bool
    {
        // Extract the plugins array content
        $pluginsArrayContent = $this->extractPluginsArrayContent($content);

        if ($pluginsArrayContent === null) {
            // No plugins array found, so plugin is not registered
            return false;
        }

        // Check for full namespace in plugins array
        $escapedPluginClass = preg_quote($pluginClass, '/');
        if (preg_match('/'.$escapedPluginClass.'::(make|class)\(?\)?/', $pluginsArrayContent) ||
            preg_match('/\\\\'.$escapedPluginClass.'::(make|class)\(?\)?/', $pluginsArrayContent)) {
            return true;
        }

        // Extract short class name (e.g., "DraftPlugin" from "Moox\Draft\DraftPlugin")
        $parts = explode('\\', $pluginClass);
        $shortClassName = end($parts);

        // Check if there's a 'use' statement for this class
        $escapedFullClass = preg_quote($pluginClass, '/');
        $usePattern = '/use\s+'.$escapedFullClass.'\s*;/';

        if (preg_match($usePattern, $content)) {
            // If 'use' statement exists, check for short class name in plugins array
            $escapedShortName = preg_quote($shortClassName, '/');
            if (preg_match('/'.$escapedShortName.'::(make|class)\(?\)?/', $pluginsArrayContent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract the content inside the ->plugins([...]) array.
     * Returns null if no plugins array is found.
     */
    protected function extractPluginsArrayContent(string $content): ?string
    {
        // Try to match ->plugins([...])
        if (preg_match('/->plugins\s*\(\s*\[(.*?)\]\s*\)/s', $content, $matches)) {
            return $matches[1];
        }

        // Also check for ->plugin(...) single plugin registration
        if (preg_match('/->plugin\s*\(\s*([^)]+)\s*\)/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
