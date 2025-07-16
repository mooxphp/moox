<?php

namespace Moox\Core\Console\Traits;

trait InstallPackages
{
    use InstallPackage;

    public function installPackages(array $panelPaths): void
    {
        $packages = $this->getMooxPackages();

        foreach ($packages as $package) {
            $this->installPackage($package, $panelPaths);
        }
    }
}

