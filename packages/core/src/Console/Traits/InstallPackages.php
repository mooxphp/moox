<?php

namespace Moox\Core\Console\Traits;

trait InstallPackages
{
    use InstallPackage;

    public function installPackages(array $panelPaths): void
    {
        $packages = $this->getMooxPackages();

        $packages = array_filter(
            $packages,
            fn($p) => !empty($p) && isset($p['name'])
        );
        
        foreach ($packages as $package) {
            $this->installPackage($package, $panelPaths);
        }
        
    }
}
