<?php

namespace Moox\Monorepo\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Moox\Monorepo\Actions\CreateReleaseAction;
use Moox\Monorepo\Actions\DiscoverPackagesAction;
use Moox\Monorepo\Actions\ProcessChangelogAction;
use Moox\Monorepo\Contracts\GitHubClientInterface;
use Moox\Monorepo\Contracts\VersionManagerInterface;
use Moox\Monorepo\DataTransferObjects\ReleaseInfo;
use Moox\Monorepo\Services\RepositoryCreationService;

class ReleaseCommand extends Command
{
    protected $signature = 'moox:release 
                           {--versions= : Specify version number}
                           {--dry-run : Show what would be done without making changes}
                           {--public-only : Only process public packages}
                           {--private-only : Only process private packages}';

    protected $description = 'Create releases for monorepo packages (v2.0)';

    public function __construct(
        private GitHubClientInterface $githubClient,
        private VersionManagerInterface $versionManager,
        private DiscoverPackagesAction $packageDiscovery,
        private ProcessChangelogAction $changelogProcessor,
        private CreateReleaseAction $releaseAction,
        private RepositoryCreationService $repositoryCreationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('🚀 Starting Monorepo Release Process v2.0');

        // Validate GitHub access
        $this->line('🔐 Authenticating with GitHub...');
        if (! $this->validateGitHubAccess()) {
            return 1;
        }

        // Get current version
        $organization = config('monorepo.github.organization');
        $publicRepo = config('monorepo.github.public_repo');
        $privateRepo = config('monorepo.github.private_repo');

        $this->line('📡 Getting current version from GitHub...');
        $currentVersion = $this->versionManager->getCurrentVersion($organization, $publicRepo);

        if (! $currentVersion) {
            $currentVersion = '0.0.0';
            $this->warn('No existing release found. Starting from v0.0.1');
        }

        // Get new version
        $newVersion = $this->option('version') ?: $this->askForVersion($currentVersion);

        if (! $this->versionManager->validateVersionFormat($newVersion)) {
            $this->error('Invalid version format. Please use semantic versioning (e.g., 1.2.3)');

            return 1;
        }

        if ($this->versionManager->compareVersions($newVersion, $currentVersion) <= 0) {
            $this->error('New version must be greater than current version');

            return 1;
        }

        $this->info("📦 Current version: {$currentVersion}");
        $this->info("🎯 New version: {$newVersion}");

        // Discover packages with progress
        $this->line('🔍 Discovering packages from monorepo...');
        $publicPackages = collect();
        $privatePackages = collect();

        if (! $this->option('private-only')) {
            $this->line('   📂 Scanning public packages...');
            $publicPackages = $this->discoverPackages('public');
        }

        if (! $this->option('public-only')) {
            $this->line('   📂 Scanning private packages...');
            $privatePackages = $this->discoverPackages('private');
        }

        $allPackages = $publicPackages->concat($privatePackages);

        if ($allPackages->isEmpty()) {
            $this->warn('No packages found to release');

            return 0;
        }

        $this->info("📋 Found {$allPackages->count()} packages");

        // Process changelog with progress
        $this->line('📝 Processing changelog entries...');
        $changelogPath = config('monorepo.packages.devlog_path');
        $packageChanges = $this->processPackageChanges($allPackages, $changelogPath);

        // Show release preview
        $this->showReleasePreview($packageChanges);

        // Check for missing repositories
        $this->line('');
        $this->info('🔍 Checking for missing repositories...');
        $missingPackages = $this->checkMissingRepositories();

        if ($missingPackages->isNotEmpty()) {
            $this->displayMissingRepositories($missingPackages);

            if (! $this->option('dry-run') && $this->confirm('Create missing repositories before release?', true)) {
                $this->createMissingRepositories($missingPackages);
            }
        }

        if ($this->option('dry-run')) {
            $this->info('🏁 Dry run completed. No changes made.');

            return 0;
        }

        if (! $this->confirm('Proceed with release?', true)) {
            $this->info('Release cancelled.');

            return 0;
        }

        // Create releases
        return $this->executeRelease($newVersion, $organization, $publicRepo, $privateRepo, $packageChanges);
    }

    private function validateGitHubAccess(): bool
    {
        try {
            $user = $this->githubClient->getCurrentUser();

            if (! $user) {
                $this->error('Failed to authenticate with GitHub. Check your token.');

                return false;
            }

            $this->info("✅ Authenticated as: {$user['login']}");

            return true;
        } catch (\Exception $e) {
            $this->error("GitHub authentication failed: {$e->getMessage()}");

            return false;
        }
    }

    private function askForVersion(string $currentVersion): string
    {
        $suggested = $this->versionManager->suggestNextVersion($currentVersion);
        $suggestions = $this->versionManager->createVersionSuggestions($currentVersion);

        $this->line("Current version: {$currentVersion}");
        $this->line("Suggested version: {$suggested}");
        $this->line('');
        $this->line('Available suggestions:');

        foreach ($suggestions as $type => $version) {
            $this->line("  {$type}: {$version}");
        }

        return $this->ask('Enter new version', $suggested);
    }

    private function discoverPackages(string $type): Collection
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

        // Convert to PackageInfo objects and read local composer.json for moox-stability
        $this->line("     🔄 Processing {$packages->count()} packages...");
        $packageInfos = $packages->map(function ($package) use ($type) {
            // Read local composer.json to get actual moox-stability
            $localComposerPath = "packages/{$package['name']}/composer.json";
            $mooxStability = 'dev'; // default

            if (file_exists($localComposerPath)) {
                $composerData = json_decode(file_get_contents($localComposerPath), true);
                $mooxStability = $composerData['extra']['moox-stability'] ?? 'stable';
            }

            return new \Moox\Monorepo\DataTransferObjects\PackageInfo(
                name: $package['name'],
                path: '', // No local path since we're getting from GitHub
                visibility: $type,
                stability: $package['stability'] ?? 'stable',
                description: $package['composer']['description'] ?? null,
                composer: array_merge($package['composer'] ?? [], [
                    'moox-stability' => $mooxStability,
                ])
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

    private function processPackageChanges(Collection $packages, string $changelogPath): Collection
    {
        $this->changelogProcessor = new ProcessChangelogAction($changelogPath);

        return $packages->map(function ($package) {
            $packageChange = $this->changelogProcessor->createPackageChange(
                $package->name,
                $package->stability,
                $package->visibility // Pass the package type (public/private)
            );

            // Add moox_stability to metadata from composer extra section
            $metadata = $packageChange->metadata;
            $metadata['moox_stability'] = $package->composer['extra']['moox-stability'] ?? 'stable';

            return new \Moox\Monorepo\DataTransferObjects\PackageChange(
                packageName: $packageChange->packageName,
                changes: $packageChange->changes,
                releaseMessage: $packageChange->releaseMessage,
                changeType: $packageChange->changeType,
                packageType: $packageChange->packageType,
                metadata: $metadata
            );
        });
    }

    private function showReleasePreview(Collection $packageChanges): void
    {
        $this->info('📋 Release Preview:');

        $tableData = $packageChanges->map(function ($change) {
            return [
                $change->packageName,
                $change->changeType,
                $change->releaseMessage,
            ];
        })->toArray();

        $this->table(['Package', 'Type', 'Message'], $tableData);

        // Show workflow API payload
        $this->info('🔧 Workflow API Payload:');
        $packages = $this->releaseAction->preparePackagesForWorkflow($packageChanges);

        $payloadTable = collect($packages)->map(function ($data, $packageName) {
            return [
                $packageName,
                json_encode($data['release-message']),
                $data['change-type'] ?? 'compatibility',
            ];
        })->toArray();

        $this->table(['Package', 'Release Messages', 'Change Type'], $payloadTable);

        // Show JSON payload
        $this->info('📦 JSON Payload for Workflow:');
        $jsonPayload = json_encode($packages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->line($jsonPayload);
    }

    private function executeRelease(
        string $version,
        string $organization,
        string $publicRepo,
        string $privateRepo,
        Collection $packageChanges
    ): int {
        $this->info('🚀 Creating releases...');

        $results = [];

        // Create public repo release
        if (! $this->option('private-only')) {
            $publicPackages = $packageChanges->filter(function ($change) {
                // Find the original package to check its type
                return $change->packageType === 'public';
            });

            if ($publicPackages->isNotEmpty()) {
                $publicRelease = ReleaseInfo::create($version, $organization, $publicRepo);
                $result = $this->releaseAction->processCompleteRelease($publicRelease, $publicPackages);
                $results['public'] = $result;

                if ($result['success']) {
                    $this->info("✅ Public release created: v{$version}");
                } else {
                    $this->error("❌ Public release failed: {$result['error']}");
                }
            }
        }

        // Create private repo release
        if (! $this->option('public-only') && $privateRepo) {
            $privatePackages = $packageChanges->filter(function ($change) {
                // Find the original package to check its type
                return $change->packageType === 'private';
            });

            if ($privatePackages->isNotEmpty()) {
                $privateRelease = ReleaseInfo::create($version, $organization, $privateRepo);
                $result = $this->releaseAction->processCompleteRelease($privateRelease, $privatePackages);
                $results['private'] = $result;

                if ($result['success']) {
                    $this->info("✅ Private release created: v{$version}");
                } else {
                    $this->error("❌ Private release failed: {$result['error']}");
                }
            }
        }

        $this->info('🎉 Release process completed!');

        $successCount = collect($results)->where('success', true)->count();
        $totalCount = count($results);

        $this->info("📊 Results: {$successCount}/{$totalCount} releases successful");

        return $successCount === $totalCount ? 0 : 1;
    }

    /**
     * Check for missing repositories
     */
    private function checkMissingRepositories(): Collection
    {
        $missingPackages = collect();

        if (! $this->option('private-only')) {
            $publicMissing = $this->repositoryCreationService->findMissingRepositories('public');
            $missingPackages = $missingPackages->concat($publicMissing);
        }

        if (! $this->option('public-only')) {
            $privateMissing = $this->repositoryCreationService->findMissingRepositories('private');
            $missingPackages = $missingPackages->concat($privateMissing);
        }

        return $missingPackages;
    }

    /**
     * Display missing repositories
     */
    private function displayMissingRepositories(Collection $missingPackages): void
    {
        $this->warn("📋 Found {$missingPackages->count()} packages missing GitHub repositories:");
        $this->line('');

        $tableData = $missingPackages->map(function ($package) {
            return [
                $package->name,
                $package->visibility,
                $package->stability,
                $package->description ?: '—',
            ];
        })->toArray();

        $this->table(
            ['Package Name', 'Type', 'Stability', 'Description'],
            $tableData
        );

        $this->line('');
        $this->info('💡 These repositories will be created as empty repositories.');
        $this->info('   Settings: Issues enabled, Projects disabled, Wiki/Discussions disabled (configurable)');
        $this->info('   Use the split workflow later to populate them with package content.');
        $this->line('');
    }

    /**
     * Create missing repositories
     */
    private function createMissingRepositories(Collection $missingPackages): void
    {
        $this->info('🚀 Creating missing repositories...');
        $this->line('');

        $results = $this->repositoryCreationService->createRepositories($missingPackages);

        $this->line('');

        // Display results
        if ($results['created'] > 0) {
            $this->info("✅ Successfully created {$results['created']} empty repositories");
        }

        if ($results['devlink_updated'] > 0) {
            $this->info("🔗 Updated devlink config for {$results['devlink_updated']} packages");
        }

        if ($results['failed'] > 0) {
            $this->warn("⚠️  Failed to create {$results['failed']} repositories");
        }

        if (! empty($results['errors'])) {
            foreach ($results['errors'] as $error) {
                $this->error($error);
            }
        }

        $this->line('');
        $this->info('📊 Repository Creation Summary:');
        $this->line("Total packages processed: {$missingPackages->count()}");
        $this->line("Repositories created: {$results['created']}");
        $this->line("Devlink config updated: {$results['devlink_updated']}");
        $this->line("Failed creations: {$results['failed']}");

        if ($results['created'] > 0) {
            $this->line('');
            $this->info('🔗 Next steps:');
            $this->line('   • Run your split workflow to populate repositories with package content');
            $this->line('   • The repositories are ready to receive content from the monorepo');
        }
    }
}
