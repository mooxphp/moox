<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
        state: $wire.entangle('{{ $getStatePath() }}'),
        selectedMedia: null
    }"
         x-init="
    $wire.on('mediaSelected', data => {
        if (Array.isArray(data)) {
            data = data[0];
        }
        state = data.id;
        selectedMedia = data.url;
    });
">

        <x-filament::button
            x-on:click="$dispatch('set-media-picker-model', {
                modelId: '{{ $getRecord()->id ?? null }}',
                modelClass: '{{ addslashes($getRecord()::class) }}'
            });
            $dispatch('open-modal', { id: 'mediaPickerModal' })">
            Wähle ein Bild
        </x-filament::button>


        <template x-if="selectedMedia">
            <img :src="selectedMedia" class="mt-4 w-32 h-32 object-cover rounded-lg shadow-md" alt="Ausgewähltes Bild" />
        </template>

        <livewire:media-picker-modal id="media-picker-modal" />
    </div>
</x-dynamic-component>
