<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
        selectedMedia: {{ json_encode(
    $getRecord()?->mediaThroughUsables()?->get()->map(function ($media) {
        return [
            'id' => $media->id,
            'url' => $media->getUrl(),
            'file_name' => $media->file_name,
            'name' => $media->name
        ];
    })->toArray() ?? [],
    JSON_UNESCAPED_UNICODE
) }},
        state: @entangle($getStatePath()),
        isMultiple: {{ $field->isMultiple() ? 'true' : 'false' }},
        isAvatar: {{ $field->isAvatar() ? 'true' : 'false' }},
        
        isNestedArray: function(data) {
            return Array.isArray(data) && Array.isArray(data[0]);
        },
        
        initializeState: function() {
            if (this.isMultiple) {
                this.state = this.selectedMedia.map(media => media.id);
            } else {
                this.state = this.selectedMedia.length > 0 ? [this.selectedMedia[0].id] : [];
            }
        }
    }" x-init="
        window.addEventListener('mediaSelected', event => {
            if (isNestedArray(event.detail)) {
                selectedMedia = event.detail[0];
            } else {
                selectedMedia = event.detail;
            }
            initializeState();
        });
        initializeState();
    " class="space-y-4">

        @if ($this instanceof \Filament\Resources\Pages\EditRecord || $this instanceof \Filament\Resources\Pages\CreateRecord)
            <x-filament::button color="primary" size="sm" class="w-full flex items-center justify-center space-x-2"
                x-on:click="
                                                                                                $dispatch('set-media-picker-model', {
                                                                                                    modelId: {{ $getRecord()?->id ?? 0 }},
                                                                                                    modelClass: '{{ $getRecord() ? addslashes($getRecord()::class) : addslashes($this->getResource()::getModel()) }}'
                                                                                                });
                                                                                                $dispatch('open-modal', { id: 'mediaPickerModal' });
                                                                                            ">
                <span>Bild auswählen</span>
            </x-filament::button>
        @endif


        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4" x-show="selectedMedia.length > 0">
            <template x-for="(media, index) in selectedMedia" :key="media . id">
                <div
                    class="relative group bg-white rounded-lg shadow hover:shadow-md transition-shadow duration-300 overflow-hidden border border-gray-200">

                    <template x-if="isAvatar">
                        <x-filament::avatar x-bind:src="media.url" x-bind:alt="media.name" size="w-12 h-12" />
                    </template>

                    <template x-if="!isAvatar">
                        <img :src="media . url" :alt="media . name" class="w-full h-32 object-cover rounded-t-lg" />
                    </template>

                    @if ($this instanceof \Filament\Resources\Pages\EditRecord)
                        <div class="absolute top-0 right-0">
                            <x-filament::button color="danger" size="xs" icon="heroicon-o-x-mark"
                                x-on:click="selectedMedia.splice(index, 1); initializeState();">
                            </x-filament::button>
                        </div>
                    @endif
                </div>
            </template>
        </div>


        <div x-show="selectedMedia.length === 0"
            class="border border-dashed border-gray-300 rounded-lg p-4 bg-gray-50 flex items-center justify-center text-sm text-gray-500">
            Kein Bild ausgewählt.
        </div>

        <livewire:media-picker-modal id="media-picker-modal" :multiple="$field->isMultiple()"
            :upload-config="$field->getUploadConfig()"
            :model-class="$this->getRecord() ? get_class($this->getRecord()) : $this->getResource()::getModel()"
            :model-id="$this->getRecord()?->id" />
    </div>
</x-dynamic-component>