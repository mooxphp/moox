@php
    $currentLang = $this->lang ?: app()->getLocale();



    $currentLocalization = \Moox\Localization\Models\Localization::with('language')
        ->whereHas('language', fn($q) => $q->where('alpha2', $currentLang))
        ->first();
    $allLocalizations = \Moox\Localization\Models\Localization::with('language')->get();
@endphp

<x-filament::dropdown>
    <x-slot name="trigger">
        <x-filament::button color="gray" icon="flag-{{ $currentLang }}" size="md"
            style="min-width: 225px; justify-content: flex-start; position: relative;">
            {{ $currentLocalization?->language->common_name ?? $currentLang }}
            <div style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%);">
                <x-filament::icon-button icon="heroicon-o-chevron-down" size="xs" color="gray" tag="a" />
            </div>
        </x-filament::button>
    </x-slot>

    @foreach($allLocalizations as $locale)
        @if($locale->language->alpha2 !== $currentLang)
            @php
                $targetUrl = request()->url() . '?' . http_build_query(array_merge(request()->query(), ['lang' => $locale->language->alpha2]));

                if ($this instanceof \Filament\Resources\Pages\ListRecords) {
                } elseif ($this instanceof \Filament\Resources\Pages\ViewRecord) {
                    if ($this->record && method_exists($this->record, 'translations')) {
                        $translation = $this->record->translations()->where('locale', $locale->language->alpha2)->first();

                        if ($translation) {
                            $targetUrl = $this->getResource()::getUrl('view', ['record' => $this->record, 'lang' => $locale->language->alpha2]);
                        } else {
                            $targetUrl = $this->getResource()::getUrl('edit', ['record' => $this->record, 'lang' => $locale->language->alpha2]);
                        }
                    }
                } elseif ($this instanceof \Filament\Resources\Pages\EditRecord || $this instanceof \Filament\Resources\Pages\CreateRecord) {
                    if ($this->record && method_exists($this->record, 'translations')) {
                        $translation = $this->record->translations()->where('locale', $locale->language->alpha2)->first();

                        if ($translation) {
                            $targetUrl = $this->getResource()::getUrl('edit', ['record' => $this->record, 'lang' => $locale->language->alpha2]);
                        } else {
                            $targetUrl = $this->getResource()::getUrl('edit', ['record' => $this->record, 'lang' => $locale->language->alpha2]);
                        }
                    }
                }
            @endphp
            @if ($this instanceof \Filament\Resources\Pages\ListRecords)
                <x-filament::dropdown.list.item :href="$targetUrl" :icon="'flag-' . $locale->language->alpha2"
                    wire:click="changeLanguage('{{ $locale->language->alpha2 }}')">
                    {{ $locale->language->common_name }}
                </x-filament::dropdown.list.item>
            @else
                <x-filament::dropdown.list.item :href="$targetUrl" :icon="'flag-' . $locale->language->alpha2" tag="a">
                    {{ $locale->language->common_name }}
                </x-filament::dropdown.list.item>
            @endif
        @endif
    @endforeach
</x-filament::dropdown>