@php
    $flags = $getState(); // Example: More than 4 flags
    $visibleFlags = [];
    $remainingFlags = 0;

    try {
        if (is_array($flags)) {
            $visibleFlags = array_slice($flags, 0, 3); // Show only the first 3
            $remainingFlags = max(0, count($flags) - 3); // Count remaining flags
        }
    } catch (\Exception $e) {
        $visibleFlags = [];
        $remainingFlags = 0;
    }
    $visibleFlags = array_reverse($visibleFlags);
@endphp
<x-filament-forms::field-wrapper>
    <div style="padding-left: 30%; position: relative; height: 28px; min-width: 66px;">
        <div style="position: relative; height: 28px; min-width: 66px;">
            @foreach($visibleFlags as $revIndex => $flag)
                @php $index = count($visibleFlags) - 1 - $revIndex; @endphp
                <span style="position: absolute; left: {{ $index * 18 }}px; z-index: {{ 10 + $index }};">
                    <x-dynamic-component :component="$flag"
                        style="width: 24px; height: 24px; border-radius: 50%; background: #fff;" />
                </span>
            @endforeach

            @if($remainingFlags > 0)
                <span style="position: absolute; left: {{ count($visibleFlags) * 18 }}px; z-index: 1;">
                    <div
                        class="flex items-center justify-center w-6 h-6 text-sm font-bold text-black rounded-full bg-white">
                        +{{ $remainingFlags }}
                    </div>
                </span>
            @endif
        </div>
    </div>
</x-filament-forms::field-wrapper>