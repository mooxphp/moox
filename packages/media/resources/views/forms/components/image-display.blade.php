<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if($getState())
        <div class="flex justify-center">
            <a href="{{ $getState() }}" target="_blank" class="flex justify-center">
                <img src="{{ $getState() }}" class="rounded-lg shadow-lg w-1/2 h-auto border border-gray-300"
                    alt="{{ $getRecord()->title ?? '' }}">
            </a>
        </div>
    @endif
</x-dynamic-component>