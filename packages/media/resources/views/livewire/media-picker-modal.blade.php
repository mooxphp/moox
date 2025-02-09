<x-filament::modal id="mediaPickerModal" width="max-w-5xl">
    <x-slot name="header">
        <h2 class="text-lg font-bold">Upload & Select Media</h2>
    </x-slot>

    <!-- Media-Uploader-Komponente -->
    <div>
        @if($modelId && $modelClass)
            <livewire:media-uploader
                :model-id="$modelId"
                :model-class="$modelClass"
                collection="default"
            />
        @else
            <p>Kein gültiges Modell angegeben.</p>
        @endif
    </div>

    <!-- Medienauswahl -->
    <x-filament::section>
        <x-filament::grid class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach ($media as $item)
                <div
                    wire:click="toggleMediaSelection({{ $item->id }})"
                    class="relative border rounded-lg shadow-md overflow-hidden bg-gray-100 hover:shadow-lg transition cursor-pointer
                    {{ in_array($item->id, $selectedMediaIds) ? 'border-blue-600' : 'border-gray-200' }}"
                >
                    <!-- Bildvorschau -->
                    <img src="{{ $item->getUrl() }}" class="w-full h-32 object-cover">

                    <!-- Häkchen und visuelle Markierung -->
                    @if(in_array($item->id, $selectedMediaIds))
                        <div class="absolute inset-0 bg-blue-200 bg-opacity-50">
                            <svg class="absolute top-2 right-2 w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 01.083 1.32l-.083.094L8.707 14.707a1 1 0 01-1.497.083l-.094-.083-4-4a1 1 0 011.32-1.497l.094.083L8 12.584l7.293-7.292a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    @endif

                    <!-- Titel des Bildes -->
                    <div class="absolute bottom-0 px-2 py-1 text-white text-sm bg-black bg-opacity-50">
                        {{ $item->name }}
                    </div>
                </div>
            @endforeach
        </x-filament::grid>
    </x-filament::section>

    <!-- Modal-Footer -->
    <x-slot name="footer">
        <x-filament::button wire:click="applySelection" color="primary">
            Auswahl übernehmen
        </x-filament::button>
        <x-filament::button wire:click="$dispatch('close-modal', { id: 'mediaPickerModal' })">
            Schließen
        </x-filament::button>
    </x-slot>
</x-filament::modal>
