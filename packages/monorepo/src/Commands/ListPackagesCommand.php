<?php

namespace Moox\Monorepo\Commands;

use Illuminate\Console\Command;
use Moox\Monorepo\Actions\DiscoverPackagesAction;
use Moox\Monorepo\Actions\ProcessChangelogAction;
use Moox\Monorepo\Contracts\GitHubClientInterface;
use Moox\Monorepo\Contracts\VersionManagerInterface;

class ListPackagesCommand extends Command
{
    protected $signature = 'monorepo:list 
                           {--public : Show only public packages}
                           {--private : Show only private packages}
                           {--changes : Show packages with changes}
                           {--missing : Show packages missing from GitHub}';

    protected $description = 'List monorepo packages and their status';

    public function __construct(
        private GitHubClientInterface $githubClient,
        private VersionManagerInterface $versionManager,
        private DiscoverPackagesAction $packageDiscovery
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('📦 Monorepo Packages Overview');

        $organization = config('monorepo.github.organization');
        $publicRepo = config('monorepo.github.public_repo');
        $privateRepo = config('monorepo.github.private_repo');

        // Get current version
        $this->line('📡 Getting current monorepo version...');
        $currentVersion = $this->versionManager->getCurrentVersion($organization, $publicRepo);
        $this->info('Current monorepo version: '.($currentVersion ?: 'No release'));
        $this->line('');

        // Discover packages with progress
        $this->line('🔍 Discovering packages from monorepo...');
        $allPackages = collect();

        if (! $this->option('private')) {
            $this->line('   📂 Scanning public packages...');
            $publicPackages = $this->discoverPackages('public');
            $allPackages = $allPackages->concat($publicPackages->map(fn ($pkg) => $pkg->with(['type' => 'public'])));
            $this->line("   ✅ Found {$publicPackages->count()} public packages");
        }

        if (! $this->option('public') && $privateRepo) {
            $this->line('   📂 Scanning private packages...');
            $privatePackages = $this->discoverPackages('private');
            $allPackages = $allPackages->concat($privatePackages->map(fn ($pkg) => $pkg->with(['type' => 'private'])));
            $this->line("   ✅ Found {$privatePackages->count()} private packages");
        }

        if ($allPackages->isEmpty()) {
            $this->warn('No packages found');

            return 0;
        }

        // Filter packages based on options
        if ($this->option('missing')) {
            $this->line('🔍 Filtering packages missing repositories...');
            $allPackages = $allPackages->filter(fn ($pkg) => ! $pkg->existsInOrganization);
        }

        if ($this->option('changes')) {
            $this->line('📝 Filtering packages with changelog entries...');
            $changelogPath = config('monorepo.packages.devlog_path');
            $changelogProcessor = new ProcessChangelogAction($changelogPath);
            $allPackages = $allPackages->filter(function ($pkg) use ($changelogProcessor) {
                return $changelogProcessor->hasExplicitChanges($pkg->name);
            });
        }

        $this->line('');

        // Display packages table
        $this->displayPackagesTable($allPackages);

        // Display summary
        $this->displaySummary($allPackages);

        return 0;
    }

    private function discoverPackages(string $type): \Illuminate\Support\Collection
    {
        $organization = config('monorepo.github.organization');
        $repoName = $type === 'public'
            ? config('monorepo.github.public_repo')
            : config('monorepo.github.private_repo');

        if (! $repoName) {
            return collect();
        }

        // Get packages from GitHub monorepo (not local directories)
        $this->line("     📡 Fetching packages from {$organization}/{$repoName}...");
        $packages = $this->githubClient->getMonorepoPackages($organization, $repoName, 'packages');

        // Convert to PackageInfo objects
        $this->line("     🔄 Processing {$packages->count()} packages...");
        $packageInfos = $packages->map(function ($package) use ($type) {
            return new \Moox\Monorepo\DataTransferObjects\PackageInfo(
                name: $package['name'],
                path: '', // No local path since we're getting from GitHub
                visibility: $type,
                stability: $package['stability'] ?? 'dev',
                description: $package['composer']['description'] ?? null,
                composer: $package['composer'] ?? []
            );
        });

        // Compare with organization repositories (GitHub to GitHub comparison)
        $this->line('     📊 Comparing with organization repositories...');
        $orgRepositories = $this->githubClient->getOrganizationRepositories($organization);
        $repoNames = $orgRepositories->pluck('name')->toArray();

        return $packageInfos->map(function ($package) use ($repoNames) {
            return $package->with(['existsInOrganization' => in_array($package->name, $repoNames)]);
        });
    }

    private function getPackagesPath(string $type): ?string
    {
        // This method is no longer needed since we're not using local paths
        // But keeping for interface compatibility
        return null;
    }

    private function displayPackagesTable(\Illuminate\Support\Collection $packages): void
    {
        $tableData = $packages->map(function ($package) {
            $hasRepo = $package->existsInOrganization ? '✅' : '❌';
            $type = $package->type ?? $package->visibility;
            $stability = $package->stability;

            return [
                $package->name,
                $type,
                $stability,
                $hasRepo,
                $package->description ?: '—',
            ];
        })->toArray();

        $this->table(
            ['Package (From Monorepo)', 'Type', 'Stability', 'Has Own Repo', 'Description'],
            $tableData
        );
    }

    private function displaySummary(\Illuminate\Support\Collection $packages): void
    {
        $total = $packages->count();
        $public = $packages->where('type', 'public')->count();
        $private = $packages->where('type', 'private')->count();
        $hasRepo = $packages->where('existsInOrganization', true)->count();
        $missingRepo = $total - $hasRepo;

        $this->line('');
        $this->info('📊 Summary:');
        $this->line("Total packages (from monorepos): {$total}");

        if ($public > 0) {
            $this->line("Public packages: {$public}");
        }

        if ($private > 0) {
            $this->line("Private packages: {$private}");
        }

        $this->line("Packages with own repositories: {$hasRepo}");

        if ($missingRepo > 0) {
            $this->warn("Packages missing own repositories: {$missingRepo}");
        }

        // Show stability breakdown
        $stabilities = $packages->groupBy('stability');
        if ($stabilities->isNotEmpty()) {
            $this->line('');
            $this->info('Package stability breakdown:');
            $stabilities->each(function ($packages, $stability) {
                $count = $packages->count();
                $this->line("  {$stability}: {$count}");
            });
        }
    }
}
