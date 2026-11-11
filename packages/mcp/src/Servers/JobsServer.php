<?php

namespace Moox\Mcp\Servers;

use Laravel\Mcp\Server;

#[Name('Moox Jobs')]
#[Instructions('Use this server to inspect the Moox jobs and recommend jobs to dispatch.')]
class JobsServer extends Server
{
    public function tools(): array
    {
        return [
            DispatchJobTool::class,
        ];
    }

    public function resources(): array
    {
        return [
            JobsResource::class,
        ];
    }

    public function prompts(): array
    {
        return [
            CreateJobPrompt::class,
        ];
    }
}
