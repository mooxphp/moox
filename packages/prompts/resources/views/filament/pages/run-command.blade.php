<x-filament-panels::page>
    @if (!$started)
        <div>
            <x-filament::section>
                @if (empty($availableCommands))
                    <p style="font-size: 0.875rem; color: #6b7280;">
                        Keine Commands verfügbar. Bitte konfiguriere die erlaubten Commands in der
                        <code style="padding: 0.125rem 0.25rem; background-color: #f3f4f6; border-radius: 0.25rem; font-size: 0.75rem;">
                            config/prompts.php
                        </code>.
                    </p>
                @else
                    <form wire:submit="startCommand" style="display: flex; flex-direction: column; gap: 1.25rem; margin-top: 0.5rem;">
                        <div style="display: flex; flex-direction: column; gap: 0.25rem; max-width: 480px;">
                            <label style="font-size: 0.875rem;">
                                Command
                            </label>

                            <x-filament::input.wrapper>
                                <x-filament::input.select wire:model="selectedCommand" required>
                                    <option value="">Bitte Command auswählen …</option>
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
                                Nur Commands aus der Konfiguration sind hier sichtbar.
                            </p>

                            <x-filament::button type="submit" color="primary">
                                Command starten
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
                ], key('run-command-' . $selectedCommand))
            </x-filament::section>

            <div style="margin-top: 1rem;">
                <x-filament::button wire:click="resetCommand" color="gray">
                    Zurück zur Command-Auswahl
                </x-filament::button>
            </div>
        </div>
    @endif
</x-filament-panels::page>
