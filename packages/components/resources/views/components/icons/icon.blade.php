<!-- resources/views/components/icons/icon.blade.php -->
@props([
    'name',
    'prefix' => 'google_symbols_',
    'color' => 'currentColor',
    'class' => '',
    'size' => '16',
])

<i class="{{ $prefix }}{{ $name }} {{ $class }}" style="color: {{ $color }}; font-size: {{ $size }}px;"></i>
