<?php

declare(strict_types=1);

namespace Moox\Mfa\Installers;

use Illuminate\Support\Facades\Schema;
use Moox\Core\Installer\AbstractAssetInstaller;

use function Moox\Prompts\error;
use function Moox\Prompts\note;

/**
 * Installer for moox/mfa — publishes MFA user columns and prints next steps.
 */
class MfaInstaller extends AbstractAssetInstaller
{
    public function getType(): string
    {
        return 'mfa-setup';
    }

    public function getLabel(): string
    {
        return 'MFA (App Authentication columns on users table)';
    }

    protected function getDefaultConfig(): array
    {
        $config = parent::getDefaultConfig();
        $config['priority'] = 40;

        return $config;
    }

    public function hasItemSelection(): bool
    {
        return false;
    }

    public function checkExists(string $packageName, array $items): bool
    {
        return Schema::hasTable('users')
            && Schema::hasColumn('users', 'app_authentication_secret');
    }

    public function install(array $assets): bool
    {
        try {
            note('📦 Publishing moox/mfa migrations (app authentication columns)…');

            $published = $this->publishPackageAssets('moox/mfa', 'migrations', [
                'publishTags' => [
                    'migrations' => 'mfa-migrations',
                ],
            ]);

            if (! $published) {
                note('ℹ️ Could not auto-publish via tag. Run: php artisan vendor:publish --tag=mfa-migrations');
            } else {
                note('✅ MFA migrations published. Run: php artisan migrate');
            }

            note('Next steps:');
            note('  1. Set MOOX_MFA_ENABLED=true (and optionally MOOX_MFA_REQUIRED)');
            note('  2. On App\\Models\\User implement HasAppAuthentication + HasAppAuthenticationRecovery');
            note('     (or use Filament InteractsWithAppAuthentication / InteractsWithAppAuthenticationRecovery)');
            note('  3. Register Moox\\Mfa\\Plugins\\MfaPlugin on the admin panel');
            note('  4. Ensure the panel has ->profile() so users can set up TOTP');
            note('  5. Does not replace Device-Trust (moox/user-device) or Login-Link');

            return true;
        } catch (\Throwable $e) {
            error('⚠️ MFA setup failed: '.$e->getMessage());

            return false;
        }
    }
}
