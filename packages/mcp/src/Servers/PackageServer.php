<?php

namespace Moox\Mcp\Servers;

use Laravel\Mcp\Server;

#[Name('Moox Packages')]
#[Instructions('Use this server to inspect the Moox package registry and recommend packages, bases, and installation/build decisions.')]
class PackageServer extends Server
{
    public function tools(): array
    {
        return [
            FindPackageTool::class,
            InstallPackageTool::class,
        ];
    }

    public function resources(): array
    {
        return [
            PackagesResource::class,
            AllPackageResource::class,
            PackageTypesResource::class,
            PackageFeaturesResource::class,
        ];
    }

    public function prompts(): array
    {
        return [
            FindPackagePrompt::class,
            InstallPackagePrompt::class,
        ];
    }
}
