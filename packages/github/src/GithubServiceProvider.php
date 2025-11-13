<?php

namespace Moox\Github;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class GithubServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('github')
            ->hasRoutes([
                'web',
            ])
            ->hasConfigFile()
            ->hasCommands([
                Commands\GitHubTokenCommand::class,
            ])
            ->hasMigration('add_github_token_to_user')
            ->hasConfigFile();
    }
}
