@php
    $defaultLocalization = \Moox\Localization\Models\Localization::where('is_default', true)->first();
    $defaultLang = $defaultLocalization ? $defaultLocalization->language->alpha2 : config('app.locale');

    $currentLang = $this->lang ?? request()->get('lang') ?? $defaultLang;

    // Try to find the exact localization first (with locale_variant)
    $currentLocalization = \Moox\Localization\Models\Localization::with('language')
        ->where('locale_variant', $currentLang)
        ->where('is_active_admin', true)
        ->first();

    // If not found, fallback to any active localization for this language
    if (!$currentLocalization) {
        $currentLocalization = \Moox\Localization\Models\Localization::with('language')
            ->whereHas('language', fn($q) => $q->where('alpha2', $currentLang))
            ->where('is_active_admin', true)
            ->first();
    }

    $isAdminContext = request()->is('admin/*') || request()->is('filament/*') ||
        (isset($this) && method_exists($this, 'getResource'));

    $shouldFilterLanguages = false;
    $translationUsesSoftDeletes = false;

    if (isset($this) && !($this instanceof \Filament\Resources\Pages\ListRecords) && isset($this->record) && method_exists($this->record, 'translations')) {
        $translationQuery = $this->record->translations();
        $translationModel = $translationQuery->getRelated();
        $translationUsesSoftDeletes = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($translationModel));

        if ($this instanceof \Filament\Resources\Pages\ViewRecord) {
            $allTranslations = $translationUsesSoftDeletes ? $this->record->translations()->withTrashed()->get() : $this->record->translations()->get();
            $shouldFilterLanguages = $allTranslations->isNotEmpty() && $allTranslations->every(function ($trans) use ($translationUsesSoftDeletes) {
                return $translationUsesSoftDeletes && $trans->trashed();
            });
        }
    }

    $allLocalizations = \Moox\Localization\Models\Localization::with('language')
        ->when($isAdminContext, function ($query) {
            $query->where('is_active_admin', true);
        })
        ->when(!$isAdminContext, function ($query) {
            $query->where('is_active_frontend', true);
        })
        ->when($shouldFilterLanguages, function ($query) use ($translationUsesSoftDeletes) {
            $query->whereHas('language', function ($q) use ($translationUsesSoftDeletes) {
                $translationsQuery = $translationUsesSoftDeletes ? $this->record->translations()->withTrashed() : $this->record->translations();
                $q->whereIn('alpha2', $translationsQuery->pluck('locale'));
            });
        })
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

    @foreach($allLocalizations as $locale)
        @if($locale->locale_variant !== $currentLang)
            @php
                $targetUrl = request()->url() . '?' . http_build_query(array_merge(request()->query(), ['lang' => $locale->locale_variant]));
                $hasTranslation = true;
                $translationIcon = null;
                $isRecordSoftDeleted = false;

                if ($this instanceof \Filament\Resources\Pages\ListRecords) {
                    $hasTranslation = true;
                } elseif ($this instanceof \Filament\Resources\Pages\ViewRecord) {
                    if (isset($this->record) && method_exists($this->record, 'translations')) {
                        $allTranslations = $translationUsesSoftDeletes ? $this->record->translations()->withTrashed()->get() : $this->record->translations()->get();
                        $allTranslationsDeleted = $translationUsesSoftDeletes && $allTranslations->isNotEmpty() && $allTranslations->every(function ($trans) {
                            return $trans->trashed();
                        });

                        if ($allTranslationsDeleted) {
                            $hasTranslation = $translationUsesSoftDeletes && $this->record->translations()->withTrashed()->where('locale', $locale->locale_variant)->exists();
                        } else {
                            $translation = $this->record->translations()->where('locale', $locale->locale_variant)->first();
                            $deletedTranslation = $translationUsesSoftDeletes ? $this->record->translations()->withTrashed()->where('locale', $locale->locale_variant)->whereNotNull('deleted_at')->first() : null;

                            $hasTranslation = $translation !== null;
                            $isDeleted = $deletedTranslation !== null && $translation === null;
                        }

                        if ($hasTranslation) {
                            $targetUrl = $this->getResource()::getUrl('view', ['record' => $this->record, 'lang' => $locale->locale_variant]);
                        } else {
                            $targetUrl = $this->getResource()::getUrl('edit', ['record' => $this->record, 'lang' => $locale->locale_variant]);
                        }
                    }
                } elseif ($this instanceof \Filament\Resources\Pages\EditRecord || $this instanceof \Filament\Resources\Pages\CreateRecord) {
                    if (isset($this->record) && method_exists($this->record, 'translations')) {
                        $allTranslations = $translationUsesSoftDeletes ? $this->record->translations()->withTrashed()->get() : $this->record->translations()->get();
                        $allTranslationsDeleted = $translationUsesSoftDeletes && $allTranslations->isNotEmpty() && $allTranslations->every(function ($trans) {
                            return $trans->trashed();
                        });

                        if ($allTranslationsDeleted) {
                            $hasTranslation = $translationUsesSoftDeletes && $this->record->translations()->withTrashed()->where('locale', $locale->locale_variant)->exists();
                        } else {
                            $translation = $this->record->translations()->where('locale', $locale->locale_variant)->first();
                            $deletedTranslation = $translationUsesSoftDeletes ? $this->record->translations()->withTrashed()->where('locale', $locale->locale_variant)->whereNotNull('deleted_at')->first() : null;

                            $hasTranslation = $translation !== null;
                            $isDeleted = $deletedTranslation !== null && $translation === null;
                        }

                        if ($hasTranslation) {
                            $targetUrl = $this->getResource()::getUrl('edit', ['record' => $this->record, 'lang' => $locale->locale_variant]);
                        } else {
                            $targetUrl = $this->getResource()::getUrl('edit', ['record' => $this->record, 'lang' => $locale->locale_variant]);
                        }
                    }
                }

                if (!$hasTranslation && !$isRecordSoftDeleted) {
                    if (isset($isDeleted) && $isDeleted) {
                        $translationIcon = 'heroicon-o-trash';
                        $translationStatus = 'deleted';
                    } else {
                        $translationIcon = 'heroicon-o-plus-circle';
                        $translationStatus = 'missing';
                    }
                }
            @endphp
            @if($hasTranslation)
                @if ($this instanceof \Filament\Resources\Pages\ListRecords)
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
            @elseif(!$isRecordSoftDeleted)
                @if ($this instanceof \Filament\Resources\Pages\ListRecords)
                    <x-filament::dropdown.list.item :href="$targetUrl" :icon="$locale->display_flag"
                        wire:click="changeLanguage('{{ $locale->locale_variant }}')">
                        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; gap: 8px;">
                            <span
                                style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0;">{{ $locale->display_name }}</span>
                            @if(isset($translationStatus) && $translationStatus === 'deleted')
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
                            @if(isset($translationStatus) && $translationStatus === 'deleted')
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
        @endif
    @endforeach
</x-filament::dropdown>