<?php

namespace Moox\Monorepo\Commands\Concerns;

use App\Models\User;
use Symfony\Component\Process\Process;

trait HasGitHubTokenConcern
{
    protected function getGitHubToken(): ?string
    {
        $token = User::first()?->github_token;

        if (! $token) {
            $this->error('No GitHub token found. Please link your GitHub account.');

            return null;
        }

        return $token;
    }

    protected function hasGitInstalled(): bool
    {
        $process = new Process(['git', '--version']);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Git is not installed or not accessible from command line.');

            return false;
        }

        return true;
    }

    protected function validateGitHubAccess(): bool
    {
        if (! $this->hasGitInstalled()) {
            return false;
        }

        $token = $this->getGitHubToken();
        if (! $token) {
            return false;
        }

        return true;
    }
}
