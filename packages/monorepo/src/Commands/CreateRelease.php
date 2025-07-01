<?php

namespace Moox\Monorepo\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Moox\Monorepo\Services\GitHubService;
use Moox\Monorepo\Services\ReleaseService;

class CreateRelease extends Command
{
    protected $signature = 'moox:releasing {--versions}';

    protected $description = 'Create a release for the monorepo';

    public function handle(): int
    {
        if ($this->option('versions')) {
            return $this->showVersions();
        }

        $this->info('Release creation coming soon.');

        return 0;
    }

    protected function showVersions(): int
    {
        $token = User::first()?->github_token;

        if (! $token) {
            $this->error('No GitHub token found. Please link your GitHub account.');

            return 1;
        }

        $mainRepo = config('monorepo.public_repo', 'mooxphp/moox');
        $org = 'mooxphp';

        try {
            $github = new GitHubService($token);
            $releaseService = new ReleaseService($github, $mainRepo, $org);
            $result = $releaseService->getVersionsOverview();

            $this->table(
                ['Name', 'Description', 'Full Name', 'Private', 'Visibility', 'Latest Release'],
                $result['repos']->toArray()
            );
            $this->line("Total repositories:       {$result['stats']['total']}");
            $this->info("Main repository version: {$result['version']}");
        } catch (\Throwable $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
