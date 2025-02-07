<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
        state: $wire.entangle('{{ $getStatePath() }}'),
        selectedImages: []
    }" x-init="$wire.on('mediaSelected', selectedMediaUrls => {
        selectedImages = selectedMediaUrls;
    });">

        <x-filament::button
            x-on:click="$dispatch('set-media-picker-model', {
                modelId: '{{ $getState()['modelId'] ?? null }}',
                modelClass: '{{ addslashes($getState()['modelClass'] ?? '') }}'
            });
            $dispatch('open-modal', { id: 'mediaPickerModal' })">
            Choose Image
        </x-filament::button>


        <x-filament::grid columns="2 sm:3 md:4 lg:5" class="gap-4 mt-4">
            <template x-for="imageUrl in selectedImages" :key="imageUrl">
                <x-filament::card class="relative border border-gray-300 rounded-lg shadow-md overflow-hidden">

                    <img :src="imageUrl" class="w-full h-40 object-cover rounded-lg" />

                    {{-- <x-filament::button color="danger" size="xs" icon="heroicon-o-x-mark"
                        class="absolute top-2 right-2 bg-red-500 text-white p-1 rounded-full"
                        x-on:click="
                    selectedImages = selectedImages.filter(i => i !== imageUrl);
                    $wire.set('{{ $getStatePath() }}', selectedImages[0] ?? null);
                ">
                    </x-filament::button> --}}

                </x-filament::card>
            </template>
        </x-filament::grid>


        <livewire:media-picker-modal />
    </div>
</x-dynamic-component>
