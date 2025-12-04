<div>
    @if($error)
        <x-filament::section>
            <x-slot name="heading">
                Fehler
            </x-slot>
            <p style="color: #b91c1c;">{{ $error }}</p>
        </x-filament::section>
    @elseif($isComplete)
        <x-filament::section>
            <x-slot name="heading">
                Command erfolgreich abgeschlossen!
            </x-slot>
            @if($output)
                <pre
                    style="background-color: #111827; color: #4ade80; padding: 1rem; border-radius: 0.25rem; overflow: auto; font-size: 0.875rem;">{{ $output }}</pre>
            @endif
        </x-filament::section>
    @elseif($currentPrompt)
        {{ $this->form }}
        <div style="margin-top: 1rem; display: flex; justify-content: flex-end;">
            <x-filament::button wire:click="submitPrompt" type="button" color="primary">
                Weiter
            </x-filament::button>
        </div>
    @else
        <x-filament::section>
            <x-slot name="heading">
                Command wird gestartet...
            </x-slot>
            <x-filament::loading-indicator />
        </x-filament::section>
    @endif
</div>