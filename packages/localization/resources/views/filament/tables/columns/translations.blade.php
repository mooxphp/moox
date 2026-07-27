@php
    $flags = $getState();
    $visibleFlags = [];
    $remainingFlags = 0;
    $currentLang = $this->lang ?? request()->get('lang', app()->getLocale());
    $currentLangCode = explode('_', (string) $currentLang)[0];

    try {
        if (is_array($flags)) {
            $currentLangFlag = null;
            $otherFlags = [];

            foreach ($flags as $flagData) {
                $flag = $flagData['flag'];
                $locale = (string) ($flagData['locale'] ?? '');
                $localeCode = explode('_', $locale)[0];

                // Draft stores locale_variant (de_DE); Static stores alpha2 (de).
                if ($locale === $currentLang || $localeCode === $currentLangCode) {
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

    $slotCount = count($visibleFlags) + ($remainingFlags > 0 ? 1 : 0);
    $minWidth = $slotCount > 0 ? (($slotCount - 1) * 18) + 24 : 24;
@endphp

{{-- Left-aligned fixed stack: avoid % padding / forms field-wrapper (causes row staircase). --}}
<div class="fi-ta-text">
    <div style="position: relative; height: 28px; width: {{ $minWidth }}px; min-width: {{ $minWidth }}px;">
        @foreach ($visibleFlags as $index => $flag)
            @php
                $flagComponent = str_replace(' trashed', '', $flag);
            @endphp
            <span
                style="position: absolute; top: 2px; left: {{ $index * 18 }}px; z-index: {{ 5 + $index }};"
            >
                <x-dynamic-component
                    :component="$flagComponent"
                    style="width: 24px; height: 24px; border-radius: 50%; background: #fff; display: block;"
                />
            </span>
        @endforeach

        @if ($remainingFlags > 0)
            @php
                $remainingLeft = count($visibleFlags) * 18;
                $remainingZIndex = 5 + count($visibleFlags);
            @endphp
            <span
                style="position: absolute; top: 2px; left: {{ $remainingLeft }}px; z-index: {{ $remainingZIndex }};"
            >
                <div
                    class="flex h-6 w-6 items-center justify-center rounded-full
                        border border-gray-300 bg-white text-sm font-bold text-black"
                >
                    +{{ $remainingFlags }}
                </div>
            </span>
        @endif
    </div>
</div>
