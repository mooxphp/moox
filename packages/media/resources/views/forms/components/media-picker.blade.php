<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{ open: false, selectedImage: null }">
        <x-filament::button @click="open = true">Wähle ein Bild</x-filament::button>

        <div x-show="open" @click.away="open = false"
            class="fixed inset-0 z-10 flex items-center justify-center bg-gray-500 bg-opacity-50">
            <div class="bg-white p-6 rounded-lg shadow-xl w-4/5">
                <h2 class="text-2xl font-semibold text-gray-600 mb-6">Wähle ein Bild</h2>

                <div class="grid grid-cols-3 gap-4">
                    @foreach ({{ mediaItems }} as $media)
                        <div class="rounded-lg">
                            <img src="{{ $media->getUrl() }}" alt="Bild" class="w-16 h-14 object-cover">
                            <div class="text-gray-600 text-sm p-2 rounded-md">
                                {{ $media->name }}

                            </div>
                            <div class="text-gray-600 text-sm p-2 rounded-md">
                                {{ $media->size }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    <p x-text="selectedImage ? 'Ausgewähltes Bild: ' + selectedImage : 'Kein Bild ausgewählt'"
                        class="text-sm text-gray-600"></p>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
