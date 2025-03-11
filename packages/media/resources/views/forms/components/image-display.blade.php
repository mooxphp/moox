<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if($getState())
        <style>
            .custom-image-width {
                width: 200px;
            }
        </style>
        <div class="flex justify-center">
            <a href="{{ $getState() }}" target="_blank" class="flex justify-center">
                <img src="{{ $getState() }}" class="rounded-lg shadow-lg custom-image-width h-auto border border-gray-300"
                    alt="{{ $getRecord()->title ?? '' }}">
            </a>
        </div>
    @endif
</x-dynamic-component>