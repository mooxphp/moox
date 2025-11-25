<?php

namespace Moox\Monorepo\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Moox\Monorepo\Actions\DiscoverPackagesAction;
use Moox\Monorepo\Contracts\GitHubClientInterface;
use Moox\Monorepo\DataTransferObjects\PackageInfo;
use Moox\Monorepo\Services\DevlinkService;
use function Moox\Prompts\multiselect;
use function Moox\Prompts\progress;
use function Moox\Prompts\table;

class CreateMissingRepositoriesCommand extends Command
{
    protected $signature = 'monorepo:create-missing 
                           {--public : Only create missing public repositories}
                           {--private : Only create missing private repositories}
                           {--force : Skip confirmation prompts}
                           {--dry-run : Show what would be created without making changes}
                           {--skip-devlink : Skip updating devlink configuration}';

    protected $description = 'Create missing GitHub repositories for packages in the monorepo';

    public function __construct(
        private GitHubClientInterface $githubClient,
        private DiscoverPackagesAction $packageDiscovery,
        private DevlinkService $devlinkService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('🔍 Finding missing repositories in GitHub organization');

        // Validate GitHub access
        $this->line('🔐 Authenticating with GitHub...');
        if (! $this->validateGitHubAccess()) {
            return 1;
        }

        $organization = config('monorepo.github.organization');
        $publicRepo = config('monorepo.github.public_repo');
        $privateRepo = config('monorepo.github.private_repo');

        // Discover missing packages with progress
        $this->line('🔍 Analyzing packages and repositories...');
        $missingPackages = collect();

        if (! $this->option('private') && $publicRepo) {
            $this->line('   📂 Checking public packages...');
            $publicMissing = $this->findMissingPackages('public');
            $missingPackages = $missingPackages->concat($publicMissing);
            $this->line("   ✅ Found {$publicMissing->count()} missing public repositories");
        }

        if (! $this->option('public') && $privateRepo) {
            $this->line('   📂 Checking private packages...');
            $privateMissing = $this->findMissingPackages('private');
            $missingPackages = $missingPackages->concat($privateMissing);
            $this->line("   ✅ Found {$privateMissing->count()} missing private repositories");
        }

        if ($missingPackages->isEmpty()) {
            $this->info('✅ No missing repositories found. All packages have corresponding GitHub repositories.');

            return 0;
        }

        $this->displayMissingPackages($missingPackages);

        if ($this->option('dry-run')) {
            $this->info('🏁 Dry run completed. No repositories created.');

            return 0;
        }

        // Let user select which repositories to create
        if (! $this->option('force')) {
            $selectedPackages = $this->selectPackagesToCreate($missingPackages);
            
            if ($selectedPackages->isEmpty()) {
                $this->info('No repositories selected. Operation cancelled.');

                return 0;
            }

            $missingPackages = $selectedPackages;
        }

        // Create repositories
        return $this->createRepositories($missingPackages, $organization);
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

    private function findMissingPackages(string $type): Collection
    {
        $organization = config('monorepo.github.organization');
        $repoName = $type === 'public'
            ? config('monorepo.github.public_repo')
            : config('monorepo.github.private_repo');

        if (! $repoName) {
            return collect();
        }

        // Get packages from GitHub monorepo
        $this->line("     📡 Fetching packages from {$organization}/{$repoName}...");
        $packages = $this->githubClient->getMonorepoPackages($organization, $repoName, 'packages');

        // Convert to PackageInfo objects
        $this->line("     🔄 Processing {$packages->count()} packages...");
        $packageInfos = $packages->map(function ($package) use ($type) {
            return new PackageInfo(
                name: $package['name'],
                path: '',
                visibility: $type,
                stability: $package['stability'] ?? 'dev',
                description: $package['composer']['description'] ?? null,
                composer: $package['composer'] ?? []
            );
        });

        // Compare with organization repositories
        $this->line('     📊 Comparing with organization repositories...');
        $orgRepositories = $this->githubClient->getOrganizationRepositories($organization);
        $repoNames = $orgRepositories->pluck('name')->toArray();

        // Return only packages that don't have corresponding repositories
        return $packageInfos->filter(function ($package) use ($repoNames) {
            return ! in_array($package->name, $repoNames);
        });
    }

    private function displayMissingPackages(Collection $missingPackages): void
    {
        $this->line('');
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

        table(
            ['Package Name', 'Type', 'Stability', 'Description'],
            $tableData
        );

        $this->line('');
        $this->info('💡 These repositories will be created as empty repositories.');
        $this->info('   Settings: Issues enabled, Projects disabled, Wiki/Discussions disabled (configurable)');
        $this->info('   Use the split workflow later to populate them with package content.');
        $this->line('');
    }

    private function selectPackagesToCreate(Collection $missingPackages): Collection
    {
        // Build choice options with package details and create a mapping
        $choiceToPackage = [];
        $choices = $missingPackages->map(function ($package) use (&$choiceToPackage) {
            $type = $package->visibility === 'public' ? '🔓' : '🔒';
            $description = $package->description ? ' - ' . substr($package->description, 0, 50) : '';
            $choice = "{$type} {$package->name} ({$package->visibility}){$description}";
            
            // Store mapping for easy lookup
            $choiceToPackage[$choice] = $package;
            
            return $choice;
        })->toArray();

        // Add "Select all" option at the beginning
        $selectAllOption = '✅ Select all';
        array_unshift($choices, $selectAllOption);

        $selected = multiselect(
            label: 'Which repositories would you like to create?',
            options: $choices,
            default: [],
            required: false,
            hint: 'Use SPACE to select, ENTER to confirm'
        );

        // If "Select all" was chosen, return all packages
        if (in_array($selectAllOption, $selected)) {
            return $missingPackages;
        }

        // Map selected choices back to packages using the mapping
        $selectedPackages = collect($selected)
            ->map(function ($choice) use ($choiceToPackage) {
                return $choiceToPackage[$choice] ?? null;
            })
            ->filter();

        return $selectedPackages;
    }

    private function createRepositories(Collection $missingPackages, string $organization): int
    {
        $this->info('🚀 Creating missing repositories...');
        $this->line('');

        $created = 0;
        $failed = 0;
        $devlinkUpdated = 0;

        // Use Prompts progress bar
        progress(
            label: 'Creating repositories...',
            steps: $missingPackages,
            callback: function ($package) use ($organization, &$created, &$failed, &$devlinkUpdated) {
                try {
                    $this->createSingleRepository($package, $organization);
                    $created++;

                    // Update devlink configuration
                    if (! $this->option('skip-devlink')) {
                        try {
                            $this->updateDevlinkConfig($package);
                            $devlinkUpdated++;
                        } catch (\Exception $e) {
                            $this->line('');
                            $this->warn("Failed to update devlink config for {$package->name}: {$e->getMessage()}");
                        }
                    }

                    // Small delay to avoid rate limiting
                    usleep(100000); // 0.1 seconds
                } catch (\Exception $e) {
                    $failed++;
                    $this->line('');
                    $this->error("Failed to create repository for {$package->name}: {$e->getMessage()}");
                }
            },
            hint: 'Creating repositories and updating devlink config'
        );

        $this->line('');

        // Display results
        if ($created > 0) {
            $this->info("✅ Successfully created {$created} empty repositories");
        }

        if ($devlinkUpdated > 0) {
            $this->info("🔗 Updated devlink config for {$devlinkUpdated} packages");
        }

        if ($failed > 0) {
            $this->warn("⚠️  Failed to create {$failed} repositories");
        }

        $this->line('');
        $this->info('📊 Summary:');
        $this->line("Total packages processed: {$missingPackages->count()}");
        $this->line("Repositories created: {$created}");
        $this->line("Devlink config updated: {$devlinkUpdated}");
        $this->line("Failed creations: {$failed}");

        if ($created > 0) {
            $this->line('');
            $this->info('🔗 Next steps:');
            $this->line('   • Run your split workflow to populate repositories with package content');
            $this->line('   • The repositories are ready to receive content from the monorepo');
        }

        return $failed > 0 ? 1 : 0;
    }

    private function createSingleRepository(PackageInfo $package, string $organization): void
    {
        $isPrivate = $package->visibility === 'private';

        // Use configurable repository settings (defaults based on mooxphp/jobs)
        $options = [
            'description' => $package->description ?: "Package repository for {$package->name}",
            'private' => $isPrivate,
            'visibility' => $isPrivate ? 'private' : 'public',
            // Repository features - configurable with jobs repository defaults
            'has_issues' => config('monorepo.repository.has_issues', true),
            'has_projects' => config('monorepo.repository.has_projects', false),
            'has_wiki' => config('monorepo.repository.has_wiki', false),
            'has_pages' => false, // Not configurable as GitHub requires special setup
            'has_discussions' => config('monorepo.repository.has_discussions', false),
            'allow_forking' => config('monorepo.repository.allow_forking', true),
            'web_commit_signoff_required' => config('monorepo.repository.web_commit_signoff_required', false),
            // Repository initialization - keep empty for workflow to populate
            'auto_init' => config('monorepo.repository.auto_init', false),
            'gitignore_template' => config('monorepo.repository.gitignore_template', null),
            'license_template' => config('monorepo.repository.default_license', null),
            // Merge settings - configurable repository preferences
            'allow_squash_merge' => config('monorepo.repository.allow_squash_merge', true),
            'allow_merge_commit' => config('monorepo.repository.allow_merge_commit', false),
            'allow_rebase_merge' => config('monorepo.repository.allow_rebase_merge', false),
            'allow_auto_merge' => config('monorepo.repository.allow_auto_merge', false),
            'delete_branch_on_merge' => config('monorepo.repository.delete_branch_on_merge', true),
        ];

        $result = $this->githubClient->createRepository($organization, $package->name, $options);

        if (! $result) {
            throw new \Exception('Repository creation failed - no response from GitHub API');
        }

        // Store the URL for later reference
        $url = $result['html_url'] ?? "https://github.com/{$organization}/{$package->name}";
        $package->repositoryUrl = $url;
    }

    /**
     * Update the devlink configuration with a new package
     */
    private function updateDevlinkConfig(PackageInfo $package): void
    {
        // Check if package already exists in devlink config
        if ($this->devlinkService->packageExistsInDevlink($package->name)) {
            return; // Already exists, no need to update
        }

        // Add the package to devlink config
        $this->devlinkService->addPackagesToDevlinkConfig(collect([$package]));
    }
}
