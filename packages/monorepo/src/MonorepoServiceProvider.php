<?php

namespace Moox\Monorepo;

use App\Models\User;
use Moox\Core\MooxServiceProvider;
use Moox\Monorepo\Actions\CreateReleaseAction;
use Moox\Monorepo\Actions\DiscoverPackagesAction;
use Moox\Monorepo\Actions\ProcessChangelogAction;
use Moox\Monorepo\Commands\ListPackagesCommand;
use Moox\Monorepo\Commands\ReleaseCommand;
use Moox\Monorepo\Contracts\GitHubClientInterface;
use Moox\Monorepo\Contracts\VersionManagerInterface;
use Moox\Monorepo\Services\DevlinkService;
use Moox\Monorepo\Services\GitHubClient;
use Moox\Monorepo\Services\RepositoryCreationService;
use Moox\Monorepo\Services\VersionManager;
use Spatie\LaravelPackageTools\Package;

class MonorepoServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('monorepo')
            ->hasCommands([
                ReleaseCommand::class,
            ])
            ->hasConfigFile('monorepo');
    }

    public function register(): void
    {
        parent::register();

        // Register GitHub client
        $this->app->bind(GitHubClientInterface::class, function ($app) {
            $token = $this->getGitHubToken();
            
            if (!$token) {
                throw new \RuntimeException('GitHub token not found. Please link your GitHub account.');
            }

            return new GitHubClient($token);
        });

        // Register version manager
        $this->app->bind(VersionManagerInterface::class, VersionManager::class);

        // Register actions
        $this->app->bind(DiscoverPackagesAction::class, function ($app) {
            return new DiscoverPackagesAction(
                $app->make(GitHubClientInterface::class)
            );
        });

        $this->app->bind(ProcessChangelogAction::class, function ($app) {
            $changelogPath = config('monorepo.packages.devlog_path');
            return new ProcessChangelogAction($changelogPath);
        });

        $this->app->bind(CreateReleaseAction::class, function ($app) {
            return new CreateReleaseAction(
                $app->make(GitHubClientInterface::class),
                $app->make(VersionManagerInterface::class)
            );
        });

        // Register RepositoryCreationService
        $this->app->bind(RepositoryCreationService::class, function ($app) {
            return new RepositoryCreationService(
                $app->make(GitHubClientInterface::class),
                $app->make(DevlinkService::class)
            );
        });

        // Register commands
        $this->app->bind(ReleaseCommand::class, function ($app) {
            return new ReleaseCommand(
                $app->make(GitHubClientInterface::class),
                $app->make(VersionManagerInterface::class),
                $app->make(DiscoverPackagesAction::class),
                $app->make(ProcessChangelogAction::class),
                $app->make(CreateReleaseAction::class),
                $app->make(RepositoryCreationService::class)
            );
        });

        $this->app->bind(ListPackagesCommand::class, function ($app) {
            return new ListPackagesCommand(
                $app->make(GitHubClientInterface::class),
                $app->make(VersionManagerInterface::class),
                $app->make(DiscoverPackagesAction::class)
            );
        });


    }

    /**
     * Get GitHub token from the first user
     * 
     * This is a simple implementation. In production, you might want
     * to have more sophisticated token management.
     */
    private function getGitHubToken(): ?string
    {
        // First try environment variable
        if ($token = env('GITHUB_TOKEN')) {
            return $token;
        }

        try {
            // Try to get token from first user (simplified approach)
            if (class_exists(User::class)) {
                $user = User::first();
                return $user?->github_token;
            }
        } catch (\Exception) {
            // If User model doesn't exist or table doesn't exist
        }

        return null;
    }
} 