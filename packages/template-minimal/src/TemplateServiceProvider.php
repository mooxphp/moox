<?php

namespace Moox\TemplateMinimal;

use Moox\Core\MooxServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\LaravelPackageTools\Package;

class TemplateServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('template-minimal')
            ->hasViews();
    }

    public function bootingPackage(): void
    {
        $componentPath = __DIR__.DIRECTORY_SEPARATOR.'Components';
        $namespace = 'Moox\\TemplateMinimal\\Components';

        $components = [];

        foreach ($this->scanDirectory($componentPath) as $file) {
            $relativePath = $this->getComponentRelativePath($file, $componentPath);
            $className = $this->convertPathToClassName($relativePath);
            $fullClassName = $namespace.'\\'.$className;

            if (class_exists($fullClassName)) {
                $components[] = $fullClassName;
            }
        }

        $this->loadViewComponentsAs('moox', $components);

        // Share the package build path and assets for Vite
        $packagePath = dirname(__DIR__, 1);
        $buildPath = $packagePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'build';
        $manifestPath = $buildPath . DIRECTORY_SEPARATOR . 'manifest.json';
        
        // Make assets accessible in public directory
        $publicPath = public_path();
        $publicBuildPath = $publicPath . DIRECTORY_SEPARATOR . 'template-minimal-build';
        
        // Create symlink or copy assets if build directory exists
        if (is_dir($buildPath)) {
            $manifestModified = file_exists($manifestPath) ? filemtime($manifestPath) : 0;
            $publicManifestPath = $publicBuildPath . DIRECTORY_SEPARATOR . 'manifest.json';
            $publicManifestModified = file_exists($publicManifestPath) ? filemtime($publicManifestPath) : 0;
            
            // Check if assets need to be updated
            $needsUpdate = !is_dir($publicBuildPath) || 
                          (!is_link($publicBuildPath) && $manifestModified > $publicManifestModified);
            
            if ($needsUpdate) {
                if (is_dir($publicBuildPath) && !is_link($publicBuildPath)) {
                    // Remove old directory if it exists and is not a symlink
                    $this->removeDirectory($publicBuildPath);
                }
                
                if (PHP_OS_FAMILY === 'Windows') {
                    // Windows: Try to create junction using mklink command
                    $command = 'mklink /J "' . str_replace('/', '\\', $publicBuildPath) . '" "' . str_replace('/', '\\', $buildPath) . '"';
                    @exec($command, $output, $returnCode);
                    
                    // If junction creation failed, copy the assets
                    if ($returnCode !== 0 && !is_dir($publicBuildPath)) {
                        $this->copyDirectory($buildPath, $publicBuildPath);
                    }
                } else {
                    // Unix/Linux: Create symlink
                    @symlink($buildPath, $publicBuildPath);
                    
                    // If symlink creation failed, copy the assets
                    if (!is_link($publicBuildPath) && !is_dir($publicBuildPath)) {
                        $this->copyDirectory($buildPath, $publicBuildPath);
                    }
                }
            }
        }
        
        // Use asset() helper with the public build path
        $buildUrlPath = 'template-minimal-build';
        
        // Read manifest and extract assets
        $assets = ['css' => [], 'js' => []];
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if ($manifest) {
                // Get CSS and JS files from manifest
                foreach ($manifest as $entry) {
                    if (isset($entry['css'])) {
                        foreach ($entry['css'] as $cssFile) {
                            // Use asset() helper to generate correct URL
                            $assets['css'][] = asset($buildUrlPath . '/' . $cssFile);
                        }
                    }
                    if (isset($entry['file'])) {
                        // Use asset() helper to generate correct URL
                        $assets['js'][] = asset($buildUrlPath . '/' . $entry['file']);
                    }
                }
            }
        }
        
        view()->share('templateMinimalBuildPath', $buildUrlPath);
        view()->share('templateMinimalAssets', $assets);
    }

    private function getRelativePath(string $target, string $base): string
    {
        // Normalize paths
        $target = str_replace('\\', '/', realpath($target) ?: $target);
        $base = str_replace('\\', '/', realpath($base) ?: $base);
        
        $targetParts = explode('/', trim($target, '/'));
        $baseParts = explode('/', trim($base, '/'));
        
        // Find common prefix
        $commonLength = 0;
        $minLength = min(count($targetParts), count($baseParts));
        for ($i = 0; $i < $minLength; $i++) {
            if ($targetParts[$i] === $baseParts[$i]) {
                $commonLength++;
            } else {
                break;
            }
        }
        
        // Calculate relative path
        $upLevels = count($baseParts) - $commonLength;
        $relativeParts = array_merge(
            array_fill(0, $upLevels, '..'),
            array_slice($targetParts, $commonLength)
        );
        
        return implode('/', $relativeParts);
    }
    
    private function getComponentRelativePath(string $file, string $componentPath): string
    {
        // Normalize paths to use forward slashes for consistent comparison
        $file = str_replace('\\', '/', $file);
        $componentPath = str_replace('\\', '/', $componentPath);

        // Remove the component path prefix and .php extension
        $relativePath = str_replace([$componentPath, '.php'], '', $file);

        // Remove leading/trailing slashes
        return trim($relativePath, '/');
    }

    private function convertPathToClassName(string $path): string
    {
        // Convert directory separators to namespace separators
        return str_replace('/', '\\', $path);
    }

    private function scanDirectory(string $path): array
    {
        $files = [];

        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        ) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }
    
    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $destPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }
            } else {
                copy($item->getRealPath(), $destPath);
            }
        }
    }
    
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }
        
        rmdir($directory);
    }
}
