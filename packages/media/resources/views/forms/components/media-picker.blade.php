<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
            state: $wire.entangle('{{ $getStatePath() }}'),
            selectedMedia: '{{ ($getRecord()?->mediaThroughUsables()?->first()?->getUrl()) ?? '' }}',
        }" x-init="
           $wire.on('mediaSelected', data => {
    if (Array.isArray(data) && data.length > 0) {
        state = data.map(item => item.id);
        selectedMedia = data[0].url;
    } else if (data && data.id) {
        state = data.id;
        selectedMedia = data.url;
    } else {
        state = null;
        selectedMedia = '';
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

        <div class="relative border border-gray-300 rounded-lg p-4 shadow-sm bg-gray-50 flex items-center justify-center"
            x-show="selectedMedia">
            <template x-if="selectedMedia">
                <img :src="selectedMedia" class="w-full h-auto max-w-xs max-h-64 object-cover rounded-lg"
                    alt="Ausgewähltes Bild" />
            </template>

            <div class="relative space-x-2 flex items-center" x-show="selectedMedia">
                <x-filament::button color="danger" size="xs" icon="heroicon-o-trash"
                    class="flex absolute top-2 items-center justify-center right-2 w-10 h-10"
                    x-on:click="selectedMedia = ''; state = null;">
                </x-filament::button>
            </div>
        </div>

        <div x-show="!selectedMedia"
            class="border border-dashed border-gray-300 rounded-lg p-4 bg-gray-50 flex items-center justify-center text-sm text-gray-500">
            Kein Bild ausgewählt.
        </div>

        <livewire:media-picker-modal id="media-picker-modal" :multiple="$field->isMultiple()" />
    </div>
</x-dynamic-component>