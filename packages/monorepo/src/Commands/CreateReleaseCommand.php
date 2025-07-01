<?php

namespace Moox\Monorepo\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Moox\Monorepo\Commands\Concerns\HasPackageVersions;

class CreateReleaseCommand extends Command
{
    use HasPackageVersions;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'moox:release  
    {--versions : Check current versions from GitHub} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will create a release for a package';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('versions')) {
            return $this->checkVersions();
        }

        // Main release creation logic will go here
        // $this->info('Starting release creation process...');

        // You can call checkVersions() here too if needed as part of the main flow
        // $this->checkVersions();

        // Continue with other release creation steps...

        // 1. Check the current version of this monorepo from GitHub API ✓
        // 2. Ask the user for the new version, e.g. 4.2.11
        // 3. Read directory of all packages (also private)
        // 4. If repos do not exist, create them (2nd iteration)
        // 5. For each new repo, add it to devlink.php (2nd iteration)
        // 6. Read the DEVLOG.md file
        // 7. Suggest contents from the DEVLOG.md file
        // 8. New packages without DEVLOG-entry are "Initial release"
        // 9. Otherwise, "Compatibility release" for all other packages
        // 10. Split all packages
        // 11. Create a new tag and release in all repos
        // 12. Create a new Packagist.org package or Satis (3rd iteration)
        // 13. Update the packages in the packages table (3rd iteration)
        // 14. Webplate für translation release! And look at the translation commit from webplate (3rd iteration)
    }

    /**
     * Check current versions from GitHub repositories
     */
    protected function checkVersions()
    {
        $this->info('Checking current version from GitHub...');

        $token = \App\Models\User::first()->github_token;

        $response = $this->request('https://api.github.com/user/repos?visibility=private', $token);
dd($response);
if ($response->successful()) {
    $repos = collect($response->json());
    dd('Anzahl private Repos:', $repos->count());
} else {
    dd('Fehler:', $response->status(), $response->json());
}

        if (! $token) {
            $this->error('No GitHub token found. Please connect your GitHub account first.');

            return 1;
        }

        $repo = config('monorepo.public_repo', 'mooxphp/moox');

        $this->info("Checking repository: {$repo}");

        // First, check if the repository exists
        $repoResponse = $this->request("https://api.github.com/repos/{$repo}", $token);

        $listRepos = $this->request('https://api.github.com/orgs/mooxphp/repos?type=all&per_page=100', $token);

        if (! $repoResponse->successful()) {
            $this->error("Repository {$repo} not found or not accessible:");
            $this->error($repoResponse->json()['message'] ?? $repoResponse->status());

            return 1;
        }

        if (! $listRepos->successful()) {
            $this->error('Failed to fetch organization repositories:');
            $this->error($listRepos->json()['message'] ?? $listRepos->status());

            return 1;
        }

        $repos = $listRepos->json();
        $tableData = [];

        foreach ($repos as $repository) {
            $releasesUrl = "https://api.github.com/repos/{$repository['full_name']}/releases/latest";

            $releases = $this->request($releasesUrl, $token);

            $latestRelease = 'No releases';
            if ($releases->successful()) {
                $releaseData = $releases->json();
                $latestRelease = $releaseData['name'] ?? 'No release';
            }

            $tableData[] = [
                $repository['name'],
                $repository['description'] ?? 'No description',
                $repository['full_name'],
                $repository['private'] ? 'Yes' : 'No',
                $repository['visibility'],
                $latestRelease,
            ];
        }

        $this->table(
            ['Name', 'Description', 'Full Name', 'Private', 'Visibility', 'Latest Release'],
            $tableData
        );
        $this->newLine();

        // Now check for latest release of the main repo
        $response = $this->request("https://api.github.com/repos/{$repo}/releases/latest", $token);

        if ($response->successful()) {
            $currentVersion = ltrim($response->json()['tag_name'], 'v');
            $this->info("Main repository current version: {$currentVersion}");
        } else {
            if ($response->status() === 404) {
                $this->warn('No releases found in the main repository.');
                $currentVersion = '0.0.0';
            } else {
                $this->error('Failed to fetch version from GitHub:');
                $this->error($response->json()['message'] ?? $response->status());
                $currentVersion = '0.0.0';
            }
        }

        $this->info("Working with version: {$currentVersion}");

        return $currentVersion;
    }

    protected function request($url, $token)
    {
        $headers = [
            'Accept' => 'application/vnd.github+json',
            'Authorization' => 'Bearer '.$token,
            'X-GitHub-Api-Version' => '2022-11-28',
        ];

        return Http::withHeaders($headers)->get($url);
    }
}
