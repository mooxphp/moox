<?php

namespace Moox\Prompts\Filament\Pages;

use Filament\Pages\Page;

class RunCommandPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-command-line';

    protected string $view = 'moox-prompts::filament.pages.run-command';

    protected static ?string $navigationLabel = null;

    protected static ?string $title = null;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? __('moox-prompts::prompts.ui.navigation_label');
    }

    public function getTitle(): string
    {
        return static::$title ?? __('moox-prompts::prompts.ui.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup ?? __('moox-prompts::prompts.ui.navigation_group');
    }

    protected static ?int $navigationSort = 100;

    public string $selectedCommand = '';

    public array $availableCommands = [];

    public bool $started = false;

    public bool $commandCompleted = false;

    protected $listeners = ['command-completed' => 'onCommandCompleted'];

    public function mount(): void
    {
        $this->availableCommands = $this->getAvailableCommands();
    }

    public function onCommandCompleted(): void
    {
        $this->commandCompleted = true;
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
        $this->commandCompleted = false;
    }

    public function getButtonText(): string
    {
        if ($this->commandCompleted) {
            return __('moox-prompts::prompts.ui.start_new_command');
        }

        return __('moox-prompts::prompts.ui.back_to_selection');
    }

    public function getButtonColor(): string
    {
        if ($this->commandCompleted) {
            return 'primary';
        }

        return 'warning';
    }

    public function getButtonKey(): string
    {
        if ($this->commandCompleted) {
            return 'new';
        }

        return 'back';
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
