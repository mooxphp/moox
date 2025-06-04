<div {{ $attributes->merge(['class' => 'tooltip']) }}
    @if ($message) data-tip="{{ $message }}" @endif>

    {{ $slot }}
</div>
