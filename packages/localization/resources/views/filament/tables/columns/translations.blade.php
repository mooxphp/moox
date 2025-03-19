@php
    $flags = $getState(); // Example: More than 4 flags
    $visibleFlags = array_slice($flags, 0, 3); // Show only the first 4
    $remainingFlags = count($flags) - 4; // Count remaining flags
@endphp
<x-filament-forms::field-wrapper>

    <div class="flex">
        @foreach($visibleFlags as $index => $flag)
            <div class="relative" style="margin-left: -{{ $index * 4 }}px">
                <x-dynamic-component :component="$flag" class="w-6 h-6 rounded-full" />
            </div>
        @endforeach

        @if($remainingFlags > 0)
            <div class="relative" style="margin-left: -4px">
                <div class="w-6 h-6 flex items-center justify-center text-sm font-bold text-black rounded-full">
                    +{{ $remainingFlags }}
                </div>
            </div>
        @endif
    </div>
</x-filament-forms::field-wrapper>
