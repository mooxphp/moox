<?php

namespace Moox\Prompts\Filament\Pages;

use Filament\Pages\Page;

class RunCommandPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-command-line';
    
    protected string $view = 'moox-prompts::filament.pages.run-command';
    
    protected static ?string $navigationLabel = 'Command Runner';
    
    protected static ?string $title = 'Command Runner';
    
    protected static string | \UnitEnum | null $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 100;

    public string $selectedCommand = '';
    public array $availableCommands = [];
    public bool $started = false;

    public function mount(): void
    {
        $this->availableCommands = $this->getAvailableCommands();
    }

    public function startCommand(): void
    {
        if ($this->selectedCommand) {
            $this->started = true;
        }
    }

    public function resetCommand(): void
    {
        $this->started = false;
        $this->selectedCommand = '';
    }

    protected function getAvailableCommands(): array
    {
        $allowedCommands = config('prompts.allowed_commands', []);
        
        if (empty($allowedCommands)) {
            return [];
        }
        
        $allCommands = app(\Illuminate\Contracts\Console\Kernel::class)->all();
        
        $available = [];
        foreach ($allowedCommands as $commandName) {
            if (isset($allCommands[$commandName])) {
                $command = $allCommands[$commandName];
                $available[$commandName] = $command->getDescription() ?: $commandName;
            }
        }
        
        ksort($available);
        
        return $available;
    }
}
