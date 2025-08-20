<?php

namespace Moox\Core\Console\Traits;

trait InstallPackages
{
    use InstallPackage;

    public function installPackages(array $panelPaths): void
    {
        $packageNames = method_exists($this, 'getMooxPackages')
            ? $this->getMooxPackages()
            : [];

        $normalizedPanelPaths = array_values(array_filter(array_map(function ($panel) {
            if (is_string($panel) && str_ends_with($panel, 'PanelProvider.php')) {
                return $panel;
            }
            if (is_string($panel) && isset($this->panelMap[$panel]['path'])) {
                $provider = $this->panelMap[$panel]['path'] . '/' . ucfirst($panel) . 'PanelProvider.php';
                return $provider;
            }
            return null;
        }, $panelPaths)));

        $packageDescriptors = array_map(
            fn(string $name) => ['name' => $name, 'composer' => $name],
            array_filter($packageNames, fn($p) => is_string($p) && $p !== '')
        );

        foreach ($packageDescriptors as $package) {
            $this->installPackage($package, $normalizedPanelPaths);
        }

    }
}
