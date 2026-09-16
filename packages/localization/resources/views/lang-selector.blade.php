@php
    $selector = \Moox\Localization\Support\LanguageSelector::for($this ?? null)->toArray();
    $currentLang = $selector['currentLang'];
    $currentLocalization = $selector['currentLocalization'];
    $isListRecords = $selector['isListRecords'];
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

    @foreach($selector['items'] as $item)
        @php
            $locale = $item['localization'];
            $targetUrl = $item['targetUrl'];
            $hasTranslation = $item['hasTranslation'];
            $translationStatus = $item['translationStatus'];
        @endphp
        @if($hasTranslation)
            @if ($isListRecords)
                <x-filament::dropdown.list.item :href="$targetUrl" :icon="$locale->display_flag"
                    wire:click="changeLanguage('{{ $locale->locale_variant }}')">
                    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                        <span>{{ $locale->display_name }}</span>
                    </div>
                </x-filament::dropdown.list.item>
            @else
                <x-filament::dropdown.list.item :href="$targetUrl" :icon="$locale->display_flag" tag="a">
                    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                        <span>{{ $locale->display_name }}</span>
                    </div>
                </x-filament::dropdown.list.item>
            @endif
        @elseif(! $item['isRecordSoftDeleted'])
            @if ($isListRecords)
                <x-filament::dropdown.list.item :href="$targetUrl" :icon="$locale->display_flag"
                    wire:click="changeLanguage('{{ $locale->locale_variant }}')">
                    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; gap: 8px;">
                        <span
                            style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0;">{{ $locale->display_name }}</span>
                        @if($translationStatus === 'deleted')
                            <x-filament::icon-button icon="heroicon-o-trash" size="md" color="danger" tooltip="Übersetzung gelöscht"
                                style="flex-shrink: 0;" />
                        @else
                            <x-filament::icon-button icon="heroicon-o-plus-circle" size="md" color="success"
                                tooltip="Übersetzung hinzufügen" style="flex-shrink: 0;" />
                        @endif
                    </div>
                </x-filament::dropdown.list.item>
            @else
                <x-filament::dropdown.list.item :href="$targetUrl" :icon="$locale->display_flag" tag="a">
                    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; gap: 8px;">
                        <span
                            style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0;">{{ $locale->display_name }}</span>
                        @if($translationStatus === 'deleted')
                            <x-filament::icon-button icon="heroicon-o-trash" size="xs" color="danger" tooltip="Übersetzung gelöscht"
                                style="flex-shrink: 0;" />
                        @else
                            <x-filament::icon-button icon="heroicon-o-plus-circle" size="xs" color="success"
                                tooltip="Übersetzung hinzufügen" style="flex-shrink: 0;" />
                        @endif
                    </div>
                </x-filament::dropdown.list.item>
            @endif
        @endif
    @endforeach
</x-filament::dropdown>
