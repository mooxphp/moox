<?php

namespace Moox\Mcp\Servers;

use Laravel\Mcp\Server;
use Moox\Mcp\Tools\Core\InstallMooxTool;

#[Name('Moox Core')]
#[Instructions('Use this server to inspect the Moox core and recommend core installations and updates.')]
class CoreServer extends Server
{
    public function tools(): array
    {
        return [
            InstallMooxTool::class,
        ];
    }

    public function resources(): array
    {
        return [
            CoreOverviewResource::class,
        ];
    }

    public function prompts(): array
    {
        return [
            InstallCorePrompt::class,
        ];
    }
}
