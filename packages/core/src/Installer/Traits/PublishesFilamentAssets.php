<?php

namespace Moox\Core\Installer\Traits;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\StringInput;

use function Moox\Prompts\info;
use function Moox\Prompts\warning;

trait PublishesFilamentAssets
{
    protected function publishFilamentAssets(): void
    {
        try {
            info('📦 Publishing Filament assets...');

            $command = $this->resolveFilamentAssetsConsoleCommand();

            if ($command) {
                $command->call('filament:assets');
            } else {
                $input = new StringInput('filament:assets');
                $input->setInteractive(true);
                app()->handleCommand($input);
            }

            info('✅ Filament assets published');
        } catch (\Throwable $e) {
            warning("⚠️ Could not publish Filament assets: {$e->getMessage()}");
        }
    }

    protected function resolveFilamentAssetsConsoleCommand(): ?Command
    {
        if (property_exists($this, 'command') && $this->command instanceof Command) {
            return $this->command;
        }

        if ($this instanceof Command) {
            return $this;
        }

        return null;
    }
}
