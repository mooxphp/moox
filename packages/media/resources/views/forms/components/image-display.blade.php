<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if($getState())
        <div class="flex justify-center">
            <img src="{{ $getState() }}" class="rounded-lg object-cover w-64 h-64 shadow-lg border border-gray-300"
                alt="{{ $getRecord()->title ?? '' }}">
        </div>
    @endif
</x-dynamic-component>