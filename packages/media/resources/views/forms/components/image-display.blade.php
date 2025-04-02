<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if($getState())
        <style>
            .custom-image-width {
                width: 200px;
            }
        </style>
        <div class="flex justify-center">
            @php
                $mimeTypeIcon = $getMimeTypeIcon();
                $isImage = str_starts_with($getRecord()->mime_type, 'image/');
            @endphp

            @if($mimeTypeIcon)
                <div class="flex flex-col justify-between items-center w-full">
                    <a href="{{ $getState() }}" target="_blank">
                        <img src="{{ $mimeTypeIcon['icon'] }}" class="w-16 h-16" alt="{{ $mimeTypeIcon['label'] }}">
                    </a>
                    <div
                        class="text-xs text-gray-700 w-full mt-2 overflow-hidden text-ellipsis whitespace-normal break-words px-2 text-center">
                        {{ $getRecord()->file_name }}
                    </div>
                </div>
            @else
                <a href="{{ $getState() }}" target="_blank">
                    <img src="{{ $getState() }}" class="rounded-lg shadow-lg custom-image-width h-auto border border-gray-300"
                        alt="{{ $getRecord()->title ?? '' }}">
                </a>
            @endif
        </div>
    @endif
</x-dynamic-component>