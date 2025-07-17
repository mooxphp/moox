<?php

namespace Moox\Github\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GitHubTokenCommand extends Command
{
    protected $signature = 'github:token 
                           {--check : Check current token}
                           {--clear : Clear GitHub token}
                           {--info : Show token info}';

    protected $description = 'Manage GitHub tokens for API access';

    public function handle(): int
    {
        $user = User::first();

        if (! $user) {
            $this->error('No user found in database. Please create a user first.');

            return 1;
        }

        if ($this->option('check')) {
            return $this->checkToken($user);
        }

        if ($this->option('clear')) {
            return $this->clearToken($user);
        }

        if ($this->option('info')) {
            return $this->showTokenInfo($user);
        }

        // Default: show current status and instructions
        return $this->showStatus($user);
    }

    private function checkToken(User $user): int
    {
        // Check environment variable first (like monorepo v2 does)
        $token = env('GITHUB_TOKEN');
        $source = 'environment';

        // Fallback to user model
        if (! $token) {
            $token = $user->github_token;
            $source = 'user model';
        }

        if (! $token) {
            $this->warn('No GitHub token found.');
            $this->showInstructions();

            return 1;
        }

        $this->info("GitHub token found in {$source}: ".substr($token, 0, 10).'...');

        // Test the token
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/vnd.github+json',
            ])->get('https://api.github.com/user');

            if ($response->successful()) {
                $userData = $response->json();
                $this->info("✅ Token is valid for user: {$userData['login']}");

                // Check scopes
                $scopes = $response->header('X-OAuth-Scopes');
                $this->line("📋 Available scopes: {$scopes}");

                $availableScopes = explode(', ', $scopes ?? '');

                // Check required scopes (with equivalent higher-level scopes)
                $scopeChecks = [
                    'repo' => ['repo'],
                    'read:org' => ['read:org', 'admin:org'], // admin:org includes read:org
                    'workflow' => ['workflow'],
                ];

                foreach ($scopeChecks as $required => $acceptable) {
                    $hasScope = false;
                    foreach ($acceptable as $scope) {
                        if (in_array($scope, $availableScopes)) {
                            $hasScope = true;
                            break;
                        }
                    }

                    if ($hasScope) {
                        $this->info("✅ {$required}");
                    } else {
                        $this->error("❌ {$required} - MISSING (required for monorepo commands)");
                    }
                }

                return 0;
            } else {
                $this->error('❌ Token is invalid or expired.');
                $this->showInstructions();

                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Failed to validate token: {$e->getMessage()}");

            return 1;
        }
    }

    private function clearToken(User $user): int
    {
        $user->update([
            'github_id' => null,
            'github_token' => null,
        ]);

        $this->info('✅ GitHub token cleared.');

        return 0;
    }

    private function showTokenInfo(User $user): int
    {
        // Check environment variable first
        $token = env('GITHUB_TOKEN');
        $source = 'environment';

        // Fallback to user model
        if (! $token) {
            $token = $user->github_token;
            $source = 'user model';
        }

        if (! $token) {
            $this->warn('No GitHub token found.');

            return 1;
        }

        $this->info('GitHub Token Information:');
        $this->line("Source: {$source}");
        $this->line("User ID: {$user->id}");
        $this->line("GitHub ID: {$user->github_id}");
        $this->line('Token: '.substr($token, 0, 10).'...'.substr($token, -4));
        $this->line('Token Type: '.(str_starts_with($token, 'gho_') ? 'OAuth Token' : 'Personal Access Token'));

        return 0;
    }

    private function showStatus(User $user): int
    {
        $this->info('🔑 GitHub Token Manager');
        $this->line('');

        // Check environment variable first
        $token = env('GITHUB_TOKEN');
        $source = 'environment';

        // Fallback to user model
        if (! $token) {
            $token = $user->github_token;
            $source = 'user model';
        }

        if ($token) {
            $this->info("✅ Token found in {$source} for user: {$user->name}");
            $this->line('Token: '.substr($token, 0, 10).'...');
            $this->line('');
            $this->line('Use --check to validate the token');
        } else {
            $this->warn('❌ No GitHub token found.');
            $this->showInstructions();
        }

        return 0;
    }

    private function showInstructions(): void
    {
        $this->line('');
        $this->info('📋 How to get a GitHub token:');
        $this->line('');
        $this->line('🌐 Option 1: Web Authentication (Recommended)');
        $this->line('   1. Make sure you are logged in to your app');
        $this->line('   2. Visit: /auth/github/connect');
        $this->line('   3. Authorize the app with GitHub');
        $this->line('');
        $this->line('🔧 Option 2: Personal Access Token');
        $this->line('   1. Go to GitHub → Settings → Developer Settings → Personal Access Tokens');
        $this->line('   2. Create token with scopes: repo, read:org, workflow');
        $this->line('   3. Add to .env: GITHUB_TOKEN=your_token_here');
        $this->line('');
        $this->line('🧪 Commands:');
        $this->line('   php artisan github:token --check    # Check token validity');
        $this->line('   php artisan github:token --info     # Show token details');
        $this->line('   php artisan github:token --clear    # Remove token');
    }
}
