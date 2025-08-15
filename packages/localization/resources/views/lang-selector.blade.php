@php
    $currentLang = request()->get('lang', app()->getLocale());
    $currentLocalization = \Moox\Localization\Models\Localization::with('language')->whereHas('language', function ($query) use ($currentLang) {
        $query->where('alpha2', $currentLang);
    })->first();
    $allLocalizations = \Moox\Localization\Models\Localization::with('language')->get();
@endphp

<x-filament::dropdown>
    <x-slot name="trigger">
        <x-filament::button color="gray" icon="flag-{{ $currentLang }}" size="md"
            style="min-width: 225px; justify-content: flex-start; padding: 10px 12px; border-radius: 8px; border: 1px solid #e5e7eb; background: white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            {{ $currentLocalization?->language->common_name ?? $currentLang }}
        </x-filament::button>
    </x-slot>

    @foreach($allLocalizations as $locale)
        @if($locale->language->alpha2 !== $currentLang)
            <x-filament::dropdown.list.item :href="request()->url() . '?' . http_build_query(array_merge(request()->query(), ['lang' => $locale->language->alpha2]))" :icon="'flag-' . $locale->language->alpha2" tag="a">
                {{ $locale->language->common_name }}
            </x-filament::dropdown.list.item>
        @endif
    @endforeach
</x-filament::dropdown>