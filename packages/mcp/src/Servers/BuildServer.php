<?php

namespace Moox\Mcp\Servers;

use Laravel\Mcp\Server;

#[Name('Moox Build')]
#[Instructions('Use this server to build Moox entities, taxonomies, modules, themes, components, and blocks.')]
class BuildServer extends Server
{
    public function tools(): array
    {
        return [
            BuildPackageTool::class,
        ];
    }

    public function resources(): array
    {
        return [
            BuildInfoResource::class,
        ];
    }

    public function prompts(): array
    {
        return [
            // später BuildPackagePrompt
        ];
    }
}
