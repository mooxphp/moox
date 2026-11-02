<?php

declare(strict_types=1);

namespace Moox\Passkey\Installers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Installer\AbstractAssetInstaller;

use function Moox\Prompts\error;
use function Moox\Prompts\note;

/**
 * Installer for moox/passkey — publishes laravel/passkeys assets and prints next steps.
 */
class PasskeyInstaller extends AbstractAssetInstaller
{
    public function getType(): string
    {
        return 'passkey-setup';
    }

    public function getLabel(): string
    {
        return 'Passkeys (laravel/passkeys migrations + config)';
    }

    protected function getDefaultConfig(): array
    {
        $config = parent::getDefaultConfig();
        $config['priority'] = 45;

        return $config;
    }

    public function hasItemSelection(): bool
    {
        return false;
    }

    public function checkExists(string $packageName, array $items): bool
    {
        return Schema::hasTable('passkeys');
    }

    public function install(array $assets): bool
    {
        try {
            $force = $this->config['force'] ?? false;

            note('📦 Publishing laravel/passkeys migrations…');
            $this->publishTag('passkeys-migrations', $force);

            note('📦 Publishing laravel/passkeys config…');
            $this->publishTag('passkeys-config', $force);

            if (! File::exists(config_path('passkeys.php'))) {
                note('ℹ️ passkeys.php may live under a different publish path — check laravel/passkeys docs.');
            }

            note('Next steps:');
            note('  1. Configure Relying Party (RP ID), HTTPS, and APP_URL for WebAuthn');
            note('  2. Set MOOX_PASSKEY_ENABLED=true (passwordless via MOOX_PASSKEY_PASSWORDLESS)');
            note('  3. User model must implement PasskeyUser + use PasskeyAuthenticatable');
            note('  4. Register Moox\\Passkey\\Plugins\\PasskeyPlugin on the admin panel');
            note('  5. Do NOT also register FilamentPasskeysPlugin — PasskeyPlugin owns it');
            note('  6. Ensure ->profile() is enabled for passkey management UI');
            note('  7. Run: php artisan migrate');
            note('  8. With moox/mfa: register PasskeyPlugin then MfaPlugin (MFA owns isRequired)');

            return true;
        } catch (\Throwable $e) {
            error('⚠️ Passkey setup failed: '.$e->getMessage());

            return false;
        }
    }

    private function publishTag(string $tag, bool $force): void
    {
        $options = [
            '--tag' => $tag,
            '--force' => $force,
        ];

        if ($this->command) {
            $this->command->call('vendor:publish', $options);
        } else {
            Artisan::call('vendor:publish', $options);
        }
    }
}
