<?php

namespace Moox\Monorepo\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Moox\Monorepo\Services\GitHubService;
use Moox\Monorepo\Services\ReleaseService;
use Moox\Monorepo\Services\PackageComparisonService;

class CreateRelease extends Command
{
    protected $signature = 'moox:releasing {--versions} {--compare-packages}';

    protected $description = 'Create a release for the monorepo';

    protected PackageComparisonService $packageComparisonService;
    protected GitHubService $githubService;

    //TODO 
        // 1. Check the current version of this monorepo from GitHub API ✓
        // 2. Ask the user for the new version, e.g. 4.2.11 ✓
        // 3. Read directory of all packages (also private) ✓
        // 4. If repos do not exist, create them (2nd iteration)
        // 5. For each new repo, add it to devlink.php (2nd iteration)
        // 6. Read the DEVLOG.md file ✓
        // 7. Suggest contents from the DEVLOG.md file ✓
        // 8. New packages without DEVLOG-entry are "Initial release"
        // 9. Otherwise, "Compatibility release" for all other packages
        // 10. Split all packages
        // Core version in composer schreiben!
        // 11. Create a new tag and release in all repos
        // 12. Create a new Packagist.org package or Satis (3rd iteration)
        // 13. Update the packages in the packages table (3rd iteration)
        // 14. Webplate für translation release! And look at the translation commit from webplate (3rd iteration)

    protected $devlog;
    public function __construct(){
        parent::__construct();
        $this->githubService = new GitHubService(User::first()?->github_token);
        $this->packageComparisonService = new PackageComparisonService($this->githubService, config('monorepo.organization', 'mooxphp'));
    }
    public function handle(): int
    {
        
        $token = User::first()?->github_token;
            if (! $token) {
                $this->error('No GitHub token found. Please link your GitHub account.');
                return 1;
            }
                

        switch (true) {
            case $this->option('versions'):
                return $this->showVersions($this->githubService);
            case $this->option('compare-packages'):
                return $this->comparePackages($this->githubService);
        }
        $currentVersion = $this->githubService->getLatestReleaseTag(config('monorepo.public_repo', 'mooxphp/moox'));

        $newVersion = $this->askForNewVersion($currentVersion);
        $this->info("New version: {$newVersion}");

        $devlog = $this->parseDevlog($newVersion);

        //TODO es soll nur erkennen, dass es neu ist
        foreach ($devlog as $package => $messages) {
            if ($this->packageComparisonService->isNewPackage($package)) {
                $this->info("New package: {$package}");
            }
        }

        return 0;
    }

    protected function showVersions(GitHubService $github): int
    {
     
        $mainRepo = config('monorepo.public_repo', 'mooxphp/moox');
        $org = 'mooxphp';

        try {
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

    protected function comparePackages(GitHubService $github): int
    {
        $publicBasePath = config('devlink.public_base_path', '../moox/packages');
        $privateBasePath = config('devlink.private_base_path', 'disabled');

        $localPackages = collect(array_merge(
            \Illuminate\Support\Facades\File::directories(base_path($publicBasePath)),
            $privateBasePath !== 'disabled' ? \Illuminate\Support\Facades\File::directories(base_path($privateBasePath)) : []
        ))->map(fn($dir) => basename($dir))
          ->toArray();

        $devlogCommits = $this->parseDevlog();

        $commitMessages = collect($localPackages)->mapWithKeys(function ($package) use ($devlogCommits) {
            if (isset($devlogCommits[$package])) {
                return [$package => $devlogCommits[$package]];
            }
            return [$package => ['Compatibility release']];
        });

       

        $this->packageComparisonService = new PackageComparisonService($github, config('monorepo.organization', 'mooxphp'));
        $devlinkPackages = $this->packageComparisonService->extractDevlinkPackages();
        $comparison = $this->packageComparisonService->comparePackagesWithRepositories($localPackages, $devlinkPackages);
        $this->table(
            ['Org. Package', 'Has single Repo'  , 'Is in Devlink Config', 'Commit Messages'], 
            $comparison->map(function($exists, $package) use ($devlinkPackages, $commitMessages) {
                $messages = $commitMessages[strtolower($package)] ?? ['Not in Monorepo'];
                return [
                    $package,
                    $exists ? '✅' : '❌',
                    in_array($package, $devlinkPackages) ? '✅' : '❌',
                    implode("\n", $messages),
                ];
            })->toArray()
        );

        return 0;
    }

    public function askForNewVersion(string $currentVersion): string
    {
        if ($currentVersion == 'No release') {
            $this->info('No existing release found. Starting with version 0.0.1.');
            $currentVersion = '0.0.1';
            $suggestedVersion = $currentVersion;
       
        } else {
            $this->info("Current version: {$currentVersion}");
            [$major, $minor, $patch] = explode('.', $currentVersion);
            $suggestedVersion = "$major.$minor." . ((int)$patch + 1);
        }

       
        $version = $this->ask("Enter the new version:", $suggestedVersion);
        
        if (! $this->validateVersionFormat($version)) {
            $this->error('Invalid version format. Please use X.X.X format.');
            return $this->askForNewVersion($currentVersion);
        }
        
        if (! $this->validateVersionOrder($version, $currentVersion)) {
            $this->error('New version cannot be smaller than the current version.');
            return $this->askForNewVersion($currentVersion);
        }
        
        return $version;
    
    }
    
    //Helper methods
    private function validateVersionFormat(string $version): bool
    {
        return preg_match('/^\\d+\\.\\d+\\.\\d+$/', $version);
    }

    private function validateVersionOrder(string $newVersion, string $currentVersion): bool
    {
        return version_compare($newVersion, $currentVersion, '>=');
    }

    protected function parseDevlog(): array
    {
        $devlogPath = base_path('packages/monorepo/DEVLOG.md');
        $content = file_get_contents($devlogPath);
        $lines = explode("\n", $content);

        $commits = [];
        $currentPackage = null;

        foreach ($lines as $line) {
            if (preg_match('/^##\s+(.*)$/', $line, $matches)) {
                $currentPackage = trim($matches[1]);
                if (!isset($commits[strtolower($currentPackage)])) {
                    $commits[strtolower($currentPackage)] = [];
                }
            } elseif ($currentPackage && preg_match('/^-\s+(.*)$/', $line, $matches)) {
                $commits[strtolower($currentPackage)][] = trim($matches[1]);
            }
        }

        return $commits;
    }
}
