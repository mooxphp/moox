@php
    $flags = $getState();
    $visibleFlags = [];
    $remainingFlags = 0;
    $currentLang = $this->lang ?? request()->get('lang', app()->getLocale());

    try {
        if (is_array($flags)) {
            $currentLangFlag = null;
            $otherFlags = [];

            foreach ($flags as $flagData) {
                $flag = $flagData['flag'];
                $locale = $flagData['locale'];

                if ($locale === $currentLang) {
                    $currentLangFlag = $flag;
                } else {
                    $otherFlags[] = $flag;
                }
            }

            if ($currentLangFlag) {
                $visibleFlags = [$currentLangFlag];
                $otherVisibleFlags = array_slice($otherFlags, 0, 2);
                $visibleFlags = array_merge($visibleFlags, $otherVisibleFlags);
            } else {
                $visibleFlags = array_slice(array_column($flags, 'flag'), 0, 3);
            }

            $remainingFlags = max(0, count($flags) - count($visibleFlags));
        }
    } catch (\Exception $e) {
        $visibleFlags = [];
        $remainingFlags = 0;
    }
@endphp

{{-- Match default TextColumn cell padding (fi-ta-text) so flags align with sibling columns. --}}
<div class="fi-ta-text w-full">
    <div class="flex w-full items-center justify-start" style="min-height: 28px;">
        @foreach ($visibleFlags as $index => $flag)
            @php
                $flagComponent = str_replace(' trashed', '', $flag);
            @endphp
            <span
                class="inline-flex shrink-0"
                style="margin-left: {{ $index === 0 ? 0 : -6 }}px; z-index: {{ 5 + $index }};"
            >
                <x-dynamic-component
                    :component="$flagComponent"
                    style="width: 24px; height: 24px; border-radius: 50%; background: #fff;"
                />
            </span>
        @endforeach

        @if ($remainingFlags > 0)
            <span
                class="inline-flex shrink-0"
                style="margin-left: {{ count($visibleFlags) > 0 ? -6 : 0 }}px; z-index: {{ 5 + count($visibleFlags) }};"
            >
                <div class="flex items-center justify-center w-6 h-6 text-sm font-bold text-black rounded-full bg-white border border-gray-300">
                    +{{ $remainingFlags }}
                </div>
            </span>
        @endif
    </div>
</div>
