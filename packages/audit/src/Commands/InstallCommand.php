<?php

declare(strict_types=1);

namespace Moox\Audit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\warning;

class InstallCommand extends Command
{
    protected $signature = 'mooxaudit:install';

    protected $description = 'Installs Moox Audit, publishes configuration, migrations and registers plugins.';

    public function handle(): void
    {
        $this->info('Moox Audit Installer');

        $this->publishConfiguration();
        $this->publishMigrations();
        $this->runMigrations();
        $this->registerPluginInPanelProvider();
        $this->info('Moox Audit installed successfully.');
    }

    public function publishConfiguration(): void
    {
        if (! confirm('Publish audit configuration?', true)) {
            return;
        }

        if (! File::exists(config_path('audit.php'))) {
            $this->callSilent('vendor:publish', ['--tag' => 'audit-config']);
            info('Published audit configuration.');

            return;
        }

        warning('config/audit.php already exists.');
    }

    public function publishMigrations(): void
    {
        if (! confirm('Publish audit migrations?', true)) {
            return;
        }

        if (Schema::hasTable('activity_log') && $this->activityLogHasMooxColumns()) {
            warning('activity_log table already exists with Moox columns. Skipping migration publish.');

            return;
        }

        if (Schema::hasTable('activity_log')) {
            warning('activity_log table exists without Moox columns. Publishing upgrade migration.');
        }

        $this->callSilent('vendor:publish', ['--tag' => 'audit-migrations']);
        info('Published audit migrations.');
    }

    private function activityLogHasMooxColumns(): bool
    {
        return Schema::hasColumn('activity_log', 'entry_type')
            && Schema::hasColumn('activity_log', 'scope')
            && Schema::hasColumn('activity_log', 'attribute_changes');
    }

    public function runMigrations(): void
    {
        if (! confirm('Run migrations?', true)) {
            return;
        }

        $this->callSilent('migrate');
        info('Migrations completed.');
    }

    public function registerPluginInPanelProvider(): void
    {
        $providerPath = app_path('Providers/Filament/AdminPanelProvider.php');

        if (! File::exists($providerPath)) {
            warning('AdminPanelProvider not found. Register Moox\\Audit\\Plugins\\AuditPlugin manually.');

            return;
        }

        $content = File::get($providerPath);

        if (str_contains($content, 'AuditPlugin')) {
            warning('AuditPlugin already registered.');

            return;
        }

        $pluginsToAdd = multiselect(
            label: 'Register these plugins:',
            options: ['AuditPlugin'],
            default: ['AuditPlugin'],
        );

        $intend = '                ';
        $namespace = '\\Moox\\Audit';
        $newPlugins = '';

        foreach ($pluginsToAdd as $plugin) {
            $newPlugins .= $intend.$namespace.'\\Plugins\\'.$plugin.'::make(),'."\n";
        }

        $pattern = '/->plugins\(\[([\s\S]*?)\]\);/';

        if (preg_match($pattern, $content)) {
            $replacement = "->plugins([$1\n{$newPlugins}\n            ]);";
            $newContent = preg_replace($pattern, $replacement, $content);
        } else {
            $pluginsSection = "            ->plugins([\n{$newPlugins}\n            ]);";
            $placeholderPattern = '/(\->authMiddleware\(\[.*?\]\))\s*\;/s';
            $newContent = preg_replace($placeholderPattern, "$1\n".$pluginsSection, $content, 1);
        }

        if (is_string($newContent)) {
            File::put($providerPath, $newContent);
            info('AuditPlugin registered in AdminPanelProvider.');
        }
    }
}
