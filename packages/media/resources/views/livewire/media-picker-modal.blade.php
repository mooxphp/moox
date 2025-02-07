<x-filament::modal id="mediaPickerModal" width="max-w-5xl" x-data
    x-on:close-modal.window="$dispatch('close-modal', { id: 'mediaPickerModal' });">

    <x-slot name="header">
        <h2 class="text-lg font-bold">Upload & Select Media</h2>
    </x-slot>

    <x-filament::section>
        @if ($modelId && $modelClass)
            <livewire:media-uploader :model-id="$modelId" :model-class="$modelClass" />
        @else
            <p class="text-gray-500">Please select a model first.</p>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-filament::grid class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @foreach ($media as $item)
                <div wire:click="toggleSelection({{ $item->id }})"
                    class="relative border rounded-lg shadow-md overflow-hidden bg-gray-100 hover:shadow-lg transition cursor-pointer"
                    style="border: {{ in_array($item->id, $selectedMedia) ? '3px solid blue' : '1px solid gray' }}">

                    <div class="w-full h-28 sm:h-32 md:h-36 lg:h-40 flex items-center justify-center bg-gray-200">
                        <img src="{{ $item->getUrl() }}" class="w-full h-full object-cover">
                    </div>

                    <button wire:click="removeMedia({{ $item->id }})"
                        class="absolute top-2 right-2 bg-red-600 text-white rounded-full p-1 text-xs">
                        ✕
                    </button>

                    @if (in_array($item->id, $selectedMedia))
                        <span class="absolute top-0 right-0 bg-blue-500 text-white px-2 py-1">✔</span>
                    @endif

                    <div class="bg-white text-gray-700 text-xs p-2 text-center truncate">
                        {{ $item->name }}
                    </div>
                </div>
            @endforeach
        </x-filament::grid>
    </x-filament::section>

    <x-slot name="footer">
        <x-filament::button wire:click="saveSelectedMedia">
            Medien übernehmen
        </x-filament::button>

        <x-filament::button x-on:click="$dispatch('close-modal', { id: 'mediaPickerModal' })">
            Close
        </x-filament::button>
    </x-slot>
</x-filament::modal>
