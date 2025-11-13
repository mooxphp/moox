<?php

declare(strict_types=1);

namespace Moox\Bpmn\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'bpmn:install';

    protected $description = 'Install the BPMN package';

    public function handle(): int
    {
        $this->info('Installing BPMN package...');

        $this->publishAssets();
        $this->publishConfig();
        $this->publishTranslations();

        $this->info('BPMN package installed successfully!');

        return Command::SUCCESS;
    }

    protected function publishAssets(): void
    {
        $this->info('Publishing assets...');

        if ($this->confirm('Publish and build assets?', true)) {
            $this->call('vendor:publish', [
                '--tag' => 'bpmn-assets',
            ]);

            $this->call('npm', ['run', 'build']);
        }
    }

    protected function publishConfig(): void
    {
        $this->info('Publishing config...');

        $this->call('vendor:publish', [
            '--tag' => 'bpmn-config',
        ]);
    }

    protected function publishTranslations(): void
    {
        $this->info('Publishing translations...');

        $this->call('vendor:publish', [
            '--tag' => 'bpmn-translations',
        ]);
    }
}
