@if (class_exists(\Moox\Localization\Models\Localization::class))
    @php
        $resolver = app(\Moox\Builder\Support\BuilderLocaleResolver::class);
        $currentLang = request()->get('lang')
            ?? session(\Moox\Builder\Support\BuilderLocaleResolver::ADMIN_SESSION_KEY)
            ?? $resolver->adminDefaultLocale();

        $currentLocalization = \Moox\Localization\Models\Localization::query()
            ->with('language')
            ->where('locale_variant', $currentLang)
            ->where('is_active_admin', true)
            ->first();

        $localizations = \Moox\Localization\Models\Localization::query()
            ->with('language')
            ->where('is_active_admin', true)
            ->orderBy('language_id')
            ->orderBy('locale_variant')
            ->get();
    @endphp

    <x-filament::dropdown>
        <x-slot name="trigger">
            <x-filament::button color="gray" icon="{{ $currentLocalization?->display_flag ?? 'flag-' . $currentLang }}"
                size="md" style="min-width: 225px; justify-content: flex-start; position: relative;">
                {{ $currentLocalization?->display_name ?? $currentLang }}
                <div style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%);">
                    <x-filament::icon-button icon="heroicon-o-chevron-down" size="xs" color="gray" tag="a" />
                </div>
            </x-filament::button>
        </x-slot>

        @foreach ($localizations as $locale)
            @if ($locale->locale_variant !== $currentLang)
                @php
                    $targetUrl = request()->url().'?'.http_build_query(array_merge(request()->query(), ['lang' => $locale->locale_variant]));
                @endphp
                <x-filament::dropdown.list.item :href="$targetUrl" :icon="$locale->display_flag" tag="a">
                    {{ $locale->display_name }}
                </x-filament::dropdown.list.item>
            @endif
        @endforeach
    </x-filament::dropdown>
@endif
