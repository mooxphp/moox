@if ($exists())
    <div role="alert" {{ $attributes }}>
        @if($icon)
            <x-moox-icon :name="$icon" class="mr-2" />
        @endif
        @if ($slot->isEmpty())
            {{ $message() }}
        @else
            {{ $slot }}
        @endif
    </div>
@endif