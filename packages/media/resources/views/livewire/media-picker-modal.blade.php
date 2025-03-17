<x-filament::modal id="mediaPickerModal" width="7xl">
    <x-slot name="header">
        <h2 class="text-lg font-bold">Upload & Select Media</h2>
    </x-slot>

    <div class="min-w-[1000px]">
        @if($modelId)
            <div class="mb-4">
                {{ $this->form }}
            </div>
        @endif

        <div class="mt-4">
        </div>
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

                @php
                    $mimeTypeLabels = [
                        'application/pdf' => [
                            'label' => 'PDF',
                            'icon' => 'heroicon-o-document-text'
                        ],
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => [
                            'label' => 'DOCX',
                            'icon' => 'heroicon-o-document-text'
                        ],
                        'application/msword' => [
                            'label' => 'DOC',
                            'icon' => 'heroicon-o-document-text'
                        ],
                        'application/vnd.ms-excel' => [
                            'label' => 'XLS',
                            'icon' => 'heroicon-o-document-text'
                        ],
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => [
                            'label' => 'XLSX',
                            'icon' => 'heroicon-o-document-text'
                        ],
                        'video/mp4' => [
                            'label' => 'MP4',
                            'icon' => 'heroicon-o-video-camera'
                        ],
                    ];
                @endphp

                <x-filament::grid class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @if($mediaItems)
                                    @foreach ($mediaItems as $item)
                                                    @php
                                                        $mimeType = $item['mime_type'];
                                                        $fileData = $mimeTypeLabels[$mimeType] ?? null;
                                                    @endphp

                                                    <div wire:click="toggleMediaSelection({{ $item['id'] }})"
                                                        class="relative rounded-lg shadow-md overflow-hidden bg-gray-100 hover:shadow-lg transition cursor-pointer
                                                                                                                                                                                                                                                                                                                                                                                                        {{ in_array($item['id'], $selectedMediaIds) ? 'ring-2 ring-blue-600' : 'border border-gray-200' }}
                                                                                                                                                                                                                                                                                                                                                                                                    {{ $selectedMediaMeta['id'] == $item['id'] ? 'ring-4 ring-blue-700 border-2 border-blue-700' : '' }}">
                                                        @if ($fileData)
                                                            <div class="flex flex-col justify-between items-center w-full h-32 bg-gray-200">
                                                                <x-filament::icon icon="{{ $fileData['icon'] }}" class="w-16 h-16 text-gray-600" />
                                                                <div
                                                                    class="text-xs text-gray-700 w-full mt-2 overflow-hidden text-ellipsis whitespace-normal break-words px-2">
                                                                    {{ $item['file_name'] }}
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="relative w-full h-32">
                                                                <img src="{{ $item['original_url'] }}" class="object-cover w-full h-full rounded-t-lg" />
                                                            </div>
                                                        @endif

                                                        @if(in_array($item['id'], $selectedMediaIds))
                                                            <div class="absolute top-1 right-1">
                                                                <x-filament::icon icon="heroicon-o-check-circle" class="w-6 h-6" fill="#3B82F6" />
                                                            </div>
                                                        @endif
                                                    </div>
                                    @endforeach
                    @endif
                </x-filament::grid>

                <div class="pt-4 mt-4">
                    <x-filament::pagination :paginator="$mediaItems" />
                </div>

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
                            <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.title" placeholder="Title"
                                :disabled="(bool) $selectedMediaMeta['write_protected']"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                        </x-filament::input.wrapper>

                        <x-filament-forms::field-wrapper.label class="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </x-filament-forms::field-wrapper.label>
                        <x-filament::input.wrapper class="mb-4">
                            <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.description"
                                placeholder="Description" :disabled="$selectedMediaMeta['write_protected'] === true"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                        </x-filament::input.wrapper>

                        <x-filament-forms::field-wrapper.label class="block text-sm font-medium text-gray-700 mb-1">
                            Internal Note
                        </x-filament-forms::field-wrapper.label>
                        <x-filament::input.wrapper class="mb-4">
                            <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.internal_note"
                                placeholder="Internal note" :disabled="$selectedMediaMeta['write_protected'] === true"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                        </x-filament::input.wrapper>

                        <x-filament-forms::field-wrapper.label class="block text-sm font-medium text-gray-700 mb-1">
                            Alt
                        </x-filament-forms::field-wrapper.label>
                        <x-filament::input.wrapper class="mb-4">
                            <x-filament::input type="text" wire:model.lazy="selectedMediaMeta.alt" placeholder="Alt text"
                                :disabled="$selectedMediaMeta['write_protected'] === true"
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
        <x-filament::button wire:click="applySelection" color="primary" class="mb-4">
            Auswahl übernehmen
        </x-filament::button>
        <x-filament::button wire:click="$dispatch('close-modal', { id: 'mediaPickerModal' })">
            Schließen
        </x-filament::button>
    </x-slot>
</x-filament::modal>