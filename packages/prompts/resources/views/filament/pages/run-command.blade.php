<x-filament-panels::page>
    @if (!$started)
        <div>
        <x-filament::section>
                @if (empty($availableCommands))
                    <p style="font-size: 0.875rem; color: #6b7280;">
                        {{ __('moox-prompts::prompts.ui.no_commands_available') }}
                        <code style="padding: 0.125rem 0.25rem; background-color: #f3f4f6; border-radius: 0.25rem; font-size: 0.75rem;">
                            config/prompts.php
                        </code>.
                    </p>
            @else
                    <form wire:submit="startCommand" style="display: flex; flex-direction: column; gap: 1.25rem; margin-top: 0.5rem;">
                        <div style="display: flex; flex-direction: column; gap: 0.25rem; max-width: 480px;">
                            <label style="font-size: 0.875rem;">
                                {{ __('moox-prompts::prompts.ui.command_label') }}
                            </label>

                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model="selectedCommand" required>
                                    <option value="">{{ __('moox-prompts::prompts.ui.select_command_placeholder') }}</option>
                                    @foreach ($availableCommands as $commandName => $description)
                                        <option value="{{ $commandName }}">
                                            {{ $commandName }} — {{ $description }}
                                        </option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                        </div>
                    
                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 0.5rem; max-width: 640px;">
                            <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">
                                {{ __('moox-prompts::prompts.ui.commands_config_hint') }}
                            </p>

                        <x-filament::button type="submit" color="primary">
                                {{ __('moox-prompts::prompts.ui.start_command_button') }}
                        </x-filament::button>
                    </div>
                </form>
            @endif
        </x-filament::section>
        </div>
    @else
        <div>
            <x-filament::section>
        @livewire('moox-prompts.filament.components.run-command-component', [
            'command' => $selectedCommand,
            'commandInput' => [],
        ], 'run-command-' . $selectedCommand)
            </x-filament::section>
        
            <div style="margin-top: 1rem;" 
                 x-data
                 x-on:command-completed.window="$wire.set('commandCompleted', true)"
                 x-on:prompts-flow-cancelled.window="$wire.resetCommand()">
            </div>
        </div>
    @endif
</x-filament-panels::page>
