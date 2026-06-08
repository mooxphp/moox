@php
    $score = $getState();
@endphp

@if($score === null)
    <span class="text-sm text-gray-400 dark:text-gray-500">–</span>
@else
    @php
        $color = match (true) {
            $score >= 100 => '#22c55e',
            $score >= 70 => '#f59e0b',
            default => '#ef4444',
        };
        $radius = 16;
        $circumference = 2 * M_PI * $radius;
        $offset = $circumference - ($score / 100) * $circumference;
    @endphp
    <div class="flex h-10 w-10 items-center justify-center">
        <svg viewBox="0 0 40 40" class="h-10 w-10">
            <circle cx="20" cy="20" r="{{ $radius }}"
                class="stroke-gray-200 dark:stroke-gray-700"
                fill="none" stroke-width="4" />
            <circle cx="20" cy="20" r="{{ $radius }}"
                    fill="none" stroke="{{ $color }}" stroke-width="4"
                    stroke-linecap="round"
                    stroke-dasharray="{{ $circumference }}"
                    stroke-dashoffset="{{ $offset }}"
                    transform="rotate(-90 20 20)" />
            <text x="20" y="20" text-anchor="middle" dominant-baseline="central"
                  fill="{{ $color }}" font-size="10" font-weight="bold">
                {{ $score }}%
            </text>
        </svg>
    </div>
@endif
