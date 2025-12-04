<x-filament-panels::page>
    @if(!$started)
        <x-filament::section>
            <x-slot name="heading">
                Command auswählen
            </x-slot>
            
            @if(empty($availableCommands))
                <p style="color: #6b7280;">Keine Commands verfügbar. Bitte konfigurieren Sie die erlaubten Commands in der Konfiguration.</p>
            @else
                <form wire:submit="startCommand">
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model="selectedCommand" required>
                            <option value="">-- Command auswählen --</option>
                            @foreach($availableCommands as $commandName => $description)
                                <option value="{{ $commandName }}">{{ $commandName }} - {{ $description }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                    
                    <div style="margin-top: 1rem;">
                        <x-filament::button type="submit" color="primary">
                            Command starten
                        </x-filament::button>
                    </div>
                </form>
            @endif
        </x-filament::section>
    @else
        @livewire('moox-prompts.filament.components.run-command-component', [
            'command' => $selectedCommand,
            'commandInput' => []
        ], key('run-command-' . $selectedCommand))
        
        <div style="margin-top: 1rem;">
            <x-filament::button wire:click="resetCommand" color="gray">
                Zurück
            </x-filament::button>
        </div>
    @endif
</x-filament-panels::page>
