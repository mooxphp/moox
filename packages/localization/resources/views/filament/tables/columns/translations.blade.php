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
<x-filament-forms::field-wrapper>
    <div style="padding-left: 30%; position: relative; height: 28px; min-width: 66px;">
        <div style="position: relative; height: 28px; min-width: 66px;">
            @foreach($visibleFlags as $index => $flag)
                    @php 
                                                        $isTrashed = str_contains($flag, 'trashed');
                        $flagComponent = str_replace(' trashed', '', $flag);
                    @endphp
                <span style="position: absolute; left: {{ $index * 18 }}px; z-index: {{ 10 + $index }};">
                                <x-dynamic-component :component="$flagComponent"
                                            style="width: 24px; height: 24px; border-radius: 50%; background: #fff;" />
                            </span>
            @endforeach
    @if($remainingFlags > 0)
        <span style="position: absolute; left: {{ (count($visibleFlags) * 18) + 8 }}px; z-index: 20;">
            <div class="flex items-center justify-center w-6 h-6 text-sm font-bold text-black rounded-full bg-white border border-gray-300">
                        +{{ $remainingFlags }}
            </div>
        </span>
    @endif
        </div>
    </div>
</x-filament-forms::field-wrapper>