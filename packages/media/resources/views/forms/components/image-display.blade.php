<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if($getState())
        <img src="{{ $getState() }}" class="rounded-lg object-cover w-full max-h-[400px]"
            alt="{{ $getRecord()->title ?? '' }}">
    @endif
</x-dynamic-component>