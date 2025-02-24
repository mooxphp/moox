<x-filament::modal id="mediaPickerModal" width="max-w-7xl">
    <x-slot name="header">
        <h2 class="text-lg font-bold">Upload & Select Media</h2>
    </x-slot>

    <div>
        @if($modelId && $modelClass)
            <livewire:media-uploader :model-id="$modelId" :model-class="$modelClass" collection="default" />
        @else
            <p>Kein gültiges Modell angegeben.</p>
        @endif
    </div>

    <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <x-filament::section>
                <div class="flex flex-row gap-4 mb-4">
                    <x-filament::input.wrapper class="w-1/2">
                        <x-filament::input type="text" wire:model.live.debounce.500ms="searchQuery"
                            placeholder="Suche nach Medien..."
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                    </x-filament::input.wrapper>

                    <x-filament::input.wrapper class="w-1/6">
                        <x-filament::input.select wire:model.live="fileTypeFilter">
                            <option value="">Alle Typen</option>
                            <option value="images">Bilder</option>
                            <option value="videos">Videos</option>
                            <option value="audios">Audios</option>
                            <option value="documents">Dokumente</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>

                    <x-filament::input.wrapper class="w-1/6">
                        <x-filament::input.select wire:model.live="dateFilter">
                            <option value="">Alle Zeiträume</option>
                            <option value="today">Heute</option>
                            <option value="week">7 Tage</option>
                            <option value="month">Monat</option>
                            <option value="year">Jahr</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <x-filament::grid class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @foreach ($media as $item)
                        <div wire:click="toggleMediaSelection({{ $item->id }})"
                            class="relative rounded-lg shadow-md overflow-hidden bg-gray-100 hover:shadow-lg transition cursor-pointer
                                                    {{ in_array($item->id, $selectedMediaIds) ? 'ring-2 ring-blue-600' : 'border border-gray-200' }}
                                                    {{ $selectedMediaMeta['id'] == $item->id ? 'ring-4 ring-blue-700 border-2 border-blue-700' : '' }}">
                            <img src="{{ $item->getUrl() }}" class="w-full h-32 object-cover rounded-t-lg">

                            @if(in_array($item->id, $selectedMediaIds))
                                <div class="absolute top-1 right-1 bg-blue-600 rounded-full shadow p-1">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 01.083 1.32l-.083.094L8.707 14.707a1 1 0 01-1.497.083l-.094-.083-4-4a1 1 0 011.32-1.497l.094.083L8 12.584l7.293-7.292a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            @endif

                            <div class="absolute bottom-0 px-2 py-1 text-white text-sm bg-black bg-opacity-50 rounded-b-lg">
                                {{ $item->name }}
                            </div>
                        </div>
                    @endforeach
                </x-filament::grid>
            </x-filament::section>

        </div>

        <div class="w-full md:w-2/5 lg:w-1/3 max-w-md flex-shrink-0 border-l pl-4">
            <x-filament::section>
                <h3 class="text-lg font-semibold mb-4">Metadaten bearbeiten</h3>

                @if(!empty($selectedMediaMeta['id']))
                    <form wire:submit.prevent="saveMetadata">
                        <x-filament-forms::field-wrapper.label class="block text-sm font-medium text-gray-700 mb-1">
                            Title
                        </x-filament-forms::field-wrapper.label>
                        <x-filament::input.wrapper class="mb-4">
                            <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.title"
                                placeholder="Titel eingeben"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                        </x-filament::input.wrapper>

                        <x-filament-forms::field-wrapper.label class="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </x-filament-forms::field-wrapper.label>
                        <x-filament::input.wrapper class="mb-4">
                            <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.description"
                                placeholder="Beschreibung eingeben"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                        </x-filament::input.wrapper>

                        <x-filament-forms::field-wrapper.label class="block text-sm font-medium text-gray-700 mb-1">
                            Internal Note
                        </x-filament-forms::field-wrapper.label>
                        <x-filament::input.wrapper class="mb-4">
                            <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.internal_note"
                                placeholder="Interne Notiz eingeben"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                        </x-filament::input.wrapper>

                        <x-filament-forms::field-wrapper.label class="block text-sm font-medium text-gray-700 mb-1">
                            Alt
                        </x-filament-forms::field-wrapper.label>
                        <x-filament::input.wrapper class="mb-4">
                            <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.alt"
                                placeholder="Alt-Text eingeben"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                        </x-filament::input.wrapper>

                        <x-filament-forms::field-wrapper.label class="block text-sm font-medium text-gray-700 mb-1">
                            File Type
                        </x-filament-forms::field-wrapper.label>
                        <x-filament::input.wrapper class="mb-4">
                            <x-filament::input type="text" disabled wire:model.lazy="selectedMediaMeta.mime_type"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                        </x-filament::input.wrapper>
                    </form>
                @else
                    <p class="text-gray-500">Kein Bild ausgewählt.</p>
                @endif
            </x-filament::section>
        </div>
    </div>




    <x-slot name="footer">
        <x-filament::button wire:click="applySelection" color="primary">
            Auswahl übernehmen
        </x-filament::button>
        <x-filament::button wire:click="$dispatch('close-modal', { id: 'mediaPickerModal' })">
            Schließen
        </x-filament::button>
    </x-slot>
</x-filament::modal>