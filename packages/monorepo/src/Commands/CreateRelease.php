<?php

namespace Moox\Monorepo\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Moox\Monorepo\Commands\Concerns\HasGitHubTokenConcern;
use Moox\Monorepo\Services\DevlogService;
use Moox\Monorepo\Services\GitHubService;
use Moox\Monorepo\Services\PackageComparisonService;
use Moox\Monorepo\Services\ReleaseService;

class CreateRelease extends Command
{
    protected $signature = 'moox:releasing {--versions} {--compare-packages}';

    protected $description = 'Create a release for the monorepo';

    protected PackageComparisonService $packageComparisonService;

    protected GitHubService $githubService;

    protected DevlogService $devlogService;

    // TODO
    // 1. Check the current version of this monorepo from GitHub API ✓
    // 2. Ask the user for the new version, e.g. 4.2.11 ✓
    // 3. Read directory of all packages (also private) ✓
    // 4. If repos do not exist, create them (2nd iteration)
    // 5. For each new repo, add it to devlink.php (2nd iteration)
    // 6. Read the DEVLOG.md file ✓
    // 7. Suggest contents from the DEVLOG.md file ✓
    // 8. New packages without DEVLOG-entry are "Initial release" ✓
    // 9. Otherwise, "Compatibility release" for all other packages ✓
    // 10. Split all packages
    // Core version in composer schreiben!
    // 11. Create a new tag and release in all repos
    // 12. Create a new Packagist.org package or Satis (3rd iteration)
    // 13. Update the packages in the packages table (3rd iteration)
    // 14. Webplate für translation release! And look at the translation commit from webplate (3rd iteration)

    use HasGitHubTokenConcern;

    public function __construct()
    {
        parent::__construct();
        $token = $this->getGitHubToken();
        $this->githubService = new GitHubService($token);
        $this->packageComparisonService = new PackageComparisonService($this->githubService, config('monorepo.organization', 'mooxphp'));
        $this->devlogService = new DevlogService($this->githubService);
    }

    public function handle(): int
    {
        if (! $this->validateGitHubAccess()) {
            return 1;
        }

        switch (true) {
            case $this->option('versions'):
                return $this->showVersions($this->githubService);
            case $this->option('compare-packages'):
                return $this->comparePackages($this->githubService);
        }

        $publicmonorepoPackages = $this->githubService->getMonorepoPackages(config('monorepo.github_org'), config('monorepo.public_repo'), config('monorepo.packages_path'));
        $privateMonorepoPackages = $this->githubService->getMonorepoPackages(config('monorepo.github_org'), config('monorepo.private_repo'), config('monorepo.packages_path'), 'private');
        $orgRepositories = $this->githubService->getOrgRepositories(config('monorepo.github_org'))->pluck('name')->toArray();

        // If i want to get repos with composer.json in it
        // $orgRepositories = $this->githubService->getOrgRepositories(config('monorepo.github_org'))
        //     ->filter(function ($repo) {
        //         $repoInfo = $this->githubService->getRepoInfo(config('monorepo.github_org') . '/' . $repo['name']);
        //         if (!$repoInfo) {
        //             return false;
        //         }
        //         $composerJson = $this->githubService->fetchJson($repoInfo['contents_url']
        //             ? str_replace('{+path}', 'composer.json', $repoInfo['contents_url'])
        //             : '');
        //         return !empty($composerJson);
        //     })
        //     ->pluck('name')
        //     ->toArray();

        $currentVersion = $this->githubService->getLatestReleaseTag(config('monorepo.github_org').'/'.config('monorepo.public_repo'));

        $newVersion = $this->askForNewVersion($currentVersion);
        $this->info("New version: {$newVersion}\n");

        $newPackages = $this->packageComparisonService->isNewOrgPackage($publicmonorepoPackages, $privateMonorepoPackages, $orgRepositories);
        if ($newPackages) {
            $newPackages = collect($newPackages)->mapWithKeys(fn ($package) => [
                $package => ['minimum-stability' => 'init'],
            ])->toArray();
        }
        if ($newPackages) {
            $this->line('New packages detected:');
            foreach ($newPackages as $package => $info) {
                $this->line("- {$package}");
            }
        }
        // Process all packages with their messages (handled by the service)
        $packagesWithMessages = $this->devlogService->processAllPackagesForRelease(array_merge($publicmonorepoPackages, $privateMonorepoPackages), $newPackages ?? []);

        $packageCount = count($packagesWithMessages);

        if ($this->confirm('Do you want to see the table with all packages and their commit messages?')) {
            $this->info("All {$packageCount} packages with their commit messages:");
            $this->table(
                ['Package', 'Messages', 'Minimum Stability'],
                $this->devlogService->sortPackagesForTable($packagesWithMessages)
            );
        }

        if (! empty($newPackages)) {
            $this->addNewPublicPackagesToDevlinkConfig($newPackages);
            // $this->changeGithubWorkflow($newPackages, $newVersion);
        }

        $this->line('Ensure that youre changes are commited to the main project and the monorepo');
        $this->line('Ensure that the split packages workflow is done');

        dd($packagesWithMessages);
        $this->waitUntilWorkflowIsDone();
        if ($this->confirm('Do you want to create a release for the monorepo?', false)) {
            $this->createRelease($newVersion);
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
                ['Name', 'Description', 'Full Name', 'Private', 'Latest Release'],
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
        ))->map(fn ($dir) => basename($dir))
            ->toArray();

        $commitMessages = $this->devlogService->getCommitMessages($localPackages);

        $this->packageComparisonService = new PackageComparisonService($github, config('monorepo.organization', 'mooxphp'));
        $devlinkPackages = $this->packageComparisonService->extractDevlinkPackages();
        $comparison = $this->packageComparisonService->comparePackagesWithRepositories($localPackages, $devlinkPackages);
        $this->table(
            ['Org. Package', 'Has single Repo', 'Is in Devlink Config', 'Commit Messages'],
            $comparison->map(function ($exists, $package) use ($devlinkPackages, $commitMessages) {
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
            $this->line('No existing release found. Starting with version 0.0.1.');
            $currentVersion = '0.0.1';
            $suggestedVersion = $currentVersion;
        } else {
            $this->line("Current version: {$currentVersion}");
            [$major, $minor, $patch] = explode('.', $currentVersion);
            $suggestedVersion = "$major.$minor.".((int) $patch + 1);
        }

        $version = $this->ask('Enter the new version:', $suggestedVersion);

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

    // Helper methods
    private function validateVersionFormat(string $version): bool
    {
        return preg_match('/^\\d+\\.\\d+\\.\\d+$/', $version);
    }

    private function validateVersionOrder(string $newVersion, string $currentVersion): bool
    {
        return version_compare($newVersion, $currentVersion, '>=');
    }

    // TODO: Not used anymore but propably helpfull
    // // Not used anymore but propably helpfull
    //     protected function copyNewPackages(array $newPackages): bool
    //     {
    //         // Ask user if they want to copy the new packages to the devlink monorepo
    //         $this->line('New packages to be copied:');
    //         foreach ($newPackages as $package) {
    //             $this->line("- {$package}");
    //         }

    //         if (! $this->confirm('Do you want to copy these packages to the devlink monorepo?', true)) {
    //             $this->line('Skipping package copying and devlink config update...');

    //             return false;
    //         }

    //         $this->line('Copying new packages to devlink monorepo...');

    //         $publicBasePath = config('devlink.public_base_path', '../moox/packages');

    //         foreach ($newPackages as $package) {
    //             $sourcePath = base_path("packages/{$package}");
    //             $targetPath = base_path("{$publicBasePath}/{$package}");

    //             if (! file_exists($sourcePath)) {
    //                 $this->warn("Source path not found for package: {$package}");

    //                 continue;
    //             }

    //             if (file_exists($targetPath)) {
    //                 $this->warn("Target path already exists for package: {$package}");

    //                 continue;
    //             }

    //             try {
    //                 \Illuminate\Support\Facades\File::copyDirectory($sourcePath, $targetPath);
    //             } catch (\Exception $e) {
    //                 $this->error("Failed to copy {$package}: ".$e->getMessage());
    //             }
    //         }

    //         return true;
    //     }

    protected function addNewPublicPackagesToDevlinkConfig(array $newPackages): void
    {
        $publicBasePath = config('devlink.public_base_path', '../moox/packages');
        $monorepoPath = realpath(base_path($publicBasePath));
        $monorepoDevlinkConfig = $monorepoPath.'/devlink/config/devlink.php';

        dump($newPackages);
        if (file_exists($monorepoDevlinkConfig)) {
            $originalContent = file_get_contents($monorepoDevlinkConfig);

            foreach ($newPackages as $package => $packageInfo) {
                $packageKey = strtolower($package);

                // Check if package already exists
                if (strpos($originalContent, "'{$packageKey}'") !== false) {
                    $this->line("Package {$packageKey} already exists, skipping...");

                    continue; // Skip if already exists
                }

                $newPackageEntry = "        '{$packageKey}' => [\n            'active' => true,\n            'path' => \$public_base_path.'/{$package}',\n            'type' => 'public',\n        ],\n";

                // Find the right alphabetical position and insert
                preg_match_all("/^        '([^']+)'/m", $originalContent, $matches);
                $existingPackages = $matches[1];

                $this->line("Adding package: {$packageKey} ({$newPackageEntry})");

                $insertAfter = null;
                foreach ($existingPackages as $existingPackage) {
                    if (strcmp($packageKey, $existingPackage) > 0) {
                        $insertAfter = $existingPackage;
                    } else {
                        break;
                    }
                }

                if ($insertAfter) {
                    // Insert after the found package
                    $pattern = "/^(        '{$insertAfter}' => \[.*?\],)\n/ms";
                    $originalContent = preg_replace($pattern, "$1\n{$newPackageEntry}", $originalContent, 1, $count);
                } else {
                    // Insert at the beginning of packages array
                    $originalContent = preg_replace("/(    'packages' => \[\n)/", "$1{$newPackageEntry}", $originalContent, 1, $count);
                }
            }
            if (! file_put_contents($monorepoDevlinkConfig, $originalContent)) {
                throw new \RuntimeException("Failed to write to devlink config file: $monorepoDevlinkConfig");
            }
        }
    }

    protected function commitToMonorepo(array $newPackages): void
    {
        $publicBasePath = config('devlink.public_base_path', '../moox/packages');
        $monorepoPath = realpath(base_path($publicBasePath));

        if (! $monorepoPath || ! is_dir($monorepoPath)) {
            $this->error("Monorepo path not found: {$publicBasePath}");

            return;
        }

        $originalDir = getcwd();

        try {
            // === FIRST: Commit to Monorepo ===
            $this->line('Committing changes to monorepo...');
            chdir($monorepoPath);

            // Add all new packages to git
            foreach ($newPackages as $package) {
                $packagePath = $package;
                if (is_dir($packagePath)) {
                    exec("git add {$packagePath}", $output, $returnCode);
                    if ($returnCode !== 0) {
                        $this->warning("Failed to add package {$package} to git");
                    }
                }
            }

            // Add devlink config changes if modified
            exec('git diff --quiet devlink/config/devlink.php', $output, $returnCode);
            if ($returnCode === 1) { // Changes exist
                exec('git add devlink/config/devlink.php', $output, $returnCode);
                if ($returnCode !== 0) {
                    $this->warning('Failed to add devlink config to git');
                }
            }

            // Commit monorepo changes
            $packageList = implode(', ', $newPackages);
            $monorepoCommitMessage = "Add new packages: {$packageList}";

            exec("git commit -m \"{$monorepoCommitMessage}\"", $output, $returnCode);
            if ($returnCode === 0) {
                $this->line('✅ Successfully committed monorepo changes');
                $this->line("Commit message: {$monorepoCommitMessage}");
            } else {
                $this->warning('Failed to commit monorepo changes (maybe no changes to commit?)');
            }

            // === SECOND: Commit to Main Project (for workflow) ===
            $this->line('Committing workflow changes to main project...');
            chdir('..');  // Go to parent directory (main project)

            // Check if workflow file exists and has changes, or if it's a new file
            $workflowExists = file_exists('.github/workflows/monorepo-split-packages.yml');
            $workflowHasChanges = false;

            if ($workflowExists) {
                exec('git diff --quiet .github/workflows/monorepo-split-packages.yml', $output, $returnCode);
                $workflowHasChanges = ($returnCode === 1); // Changes exist
            } else {
                $workflowHasChanges = true; // New file
            }

            if ($workflowHasChanges) {
                exec('git add .github/workflows/monorepo-split-packages.yml', $output, $returnCode);
                if ($returnCode === 0) {
                    $workflowCommitMessage = "Update monorepo workflow for packages: {$packageList}";
                    exec("git commit -m \"{$workflowCommitMessage}\"", $output, $returnCode);

                    if ($returnCode === 0) {
                        $this->line('✅ Successfully committed workflow changes');
                        $this->line("Commit message: {$workflowCommitMessage}");
                    } else {
                        $this->warning('Failed to commit workflow changes');
                    }
                } else {
                    $this->warning('Failed to add workflow file to git');
                }
            } else {
                $this->line('No workflow changes to commit');
            }

            // === THIRD: Optional commit for remaining changes ===
            chdir($originalDir); // Back to original directory

            // Ask user if they want to add all remaining changes
            if ($this->confirm('Do you want to add all remaining changes in the main project?', true)) {
                exec('git add .', $output, $returnCode);

                if ($returnCode === 0) {
                    // Check if there are any changes to commit
                    exec('git diff --quiet --cached', $output, $returnCode);
                    if ($returnCode === 1) { // Changes exist in staging
                        $defaultMessage = 'wip: additional changes';
                        $commitMessage = $this->ask('Enter commit message:', $defaultMessage);
                        exec("git commit -m \"{$commitMessage}\"", $output, $returnCode);

                        if ($returnCode === 0) {
                            $this->line('✅ Added all remaining changes to git');
                        } else {
                            $this->warning('Failed to commit remaining changes');
                        }
                    } else {
                        $this->line('No additional changes to commit');
                    }
                } else {
                    $this->error('Failed to add remaining changes');
                }
            }
        } catch (\Exception $e) {
            $this->error('Error during git operations: '.$e->getMessage());
        } finally {
            // Always change back to original directory
            chdir($originalDir);
        }
    }

    protected function changeGithubWorkflow(array $newPackages, string $version): void
    {
        $publicBasePath = config('devlink.public_base_path', '../moox/packages');
        $monorepoPath = realpath(base_path($publicBasePath));
        $workflowPath = dirname($monorepoPath).'/.github/workflows/monorepo-split-packages.yml';
        // Get file content
        $content = file_get_contents($workflowPath);

        // Add new packages to the workflow
        foreach ($newPackages as $package) {
            if (! str_contains($content, "- {$package}")) {
                $content = preg_replace('/(\s+- \w+\n)(?=\s+steps:)/', "$1          - {$package}\n", $content);
            }
        }

        // Sort packages alphabetically
        preg_match('/package:\s*\n((\s+- .+\n)+)/', $content, $matches);
        if (isset($matches[1])) {
            $packageLines = explode("\n", trim($matches[1]));
            $packages = array_map(function ($line) {
                return trim(str_replace('- ', '', $line));
            }, $packageLines);
            sort($packages);

            $sortedPackageList = '';
            foreach ($packages as $package) {
                $sortedPackageList .= "          - {$package}\n";
            }

            $content = preg_replace('/package:\s*\n(\s+- .+\n)+/', "package:\n{$sortedPackageList}", $content);
        }

        file_put_contents($workflowPath, $content);

        $this->line('✅ Updated GitHub workflow with '.count($newPackages).' new packages');
    }

    protected function createRelease(string $version): void
    {
        $token = User::first()?->github_token;
        $repo = config('monorepo.public_repo', 'mooxphp/moox');

        $client = new \GuzzleHttp\Client;
        $response = $client->post("https://api.github.com/repos/{$repo}/releases", [
            'headers' => [
                'Accept' => 'application/vnd.github+json',
                'Authorization' => "Bearer {$token}",
                'X-GitHub-Api-Version' => '2022-11-28',
            ],
            'json' => [
                'tag_name' => "v{$version}",
                'target_commitish' => 'main',
                'name' => "{$version}",
                'body' => "Release version {$version}, initial release",
                'draft' => false,
                'prerelease' => false,
                'generate_release_notes' => false,
            ],
        ]);

        if ($response->getStatusCode() !== 201) {
            throw new \Exception('Failed to create GitHub release. Response code: '.$response->getStatusCode());
        }

        $this->line("Creating monorepo release for version: {$version}");
    }

    protected function createReleases(): void {}
}
