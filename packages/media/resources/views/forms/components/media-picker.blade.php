<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
        selectedMedia: [],
        state: @entangle($getStatePath()),
        isMultiple: {{ $field->isMultiple() ? 'true' : 'false' }},
    }" x-init="
    window.addEventListener('mediaSelected', event => {
        console.log('Media Data:', event.detail);

        if (Array.isArray(event.detail[0])) {
            selectedMedia = event.detail[0];
        } else {
            selectedMedia = event.detail;
        }

        if (isMultiple) {
            state = selectedMedia.map(media => media.id);
        } else {
            state = selectedMedia.length > 0 ? [selectedMedia[0].id] : [];
        }
    });
" class="space-y-4">
        <x-filament::button color="primary" size="sm" class="w-full flex items-center justify-center space-x-2"
            x-on:click="
                $dispatch('set-media-picker-model', {
                    modelId: '{{ $getRecord()->id ?? null }}',
                    modelClass: '{{ addslashes($getRecord()::class) }}'
                });
                $dispatch('open-modal', { id: 'mediaPickerModal' });
        ">
            <span>Bild auswählen</span>
        </x-filament::button>

        <div class="relative border border-gray-300 rounded-lg p-4 shadow-sm bg-gray-50 flex flex-wrap gap-2"
            x-show="selectedMedia.length > 0">
            <template x-for="(media, index) in selectedMedia" :key="media . id">
                <div class="relative">
                    <img :src="media . url" class="w-32 h-32 object-cover rounded-lg" :alt="media . file_name" />
                    <x-filament::button color="danger" size="xs" icon="heroicon-o-trash"
                        class="flex absolute top-2 items-center justify-center right-2 w-8 h-8" x-on:click="
                        selectedMedia.splice(index, 1);
                        if (isMultiple) {
                            state = selectedMedia.map(media => media.id);
                        } else {
                            state = selectedMedia.length > 0 ? [selectedMedia[0].id] : [];
                        }
                    ">
                    </x-filament::button>
                </div>
            </template>
        </div>

        <div x-show="selectedMedia.length === 0"
            class="border border-dashed border-gray-300 rounded-lg p-4 shadow-sm bg-gray-50 flex items-center justify-center text-sm text-gray-500">
            Kein Bild ausgewählt.
        </div>

        <livewire:media-picker-modal id="media-picker-modal" :multiple="$field->isMultiple()" />
    </div>
</x-dynamic-component>