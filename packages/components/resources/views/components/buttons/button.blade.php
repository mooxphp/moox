@props([
    'type' => 'button',
    'icon' => null,
    'color' => 'primary',
    'outline' => false,
])

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'button']) }}
>
    @if($icon)
        <!-- x-moox-icon :name="$icon" class="mr-2" /-->
    @endif
    {{ $slot }}
</button>
