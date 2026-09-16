<?php

declare(strict_types=1);

namespace Moox\Localization\Support;

use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Moox\Localization\Models\Localization;

/**
 * Builds the Filament language-selector dropdown state.
 * Behaviour mirrors the previous inline Blade logic.
 */
final class LanguageSelector
{
    public function __construct(
        private readonly mixed $page = null,
    ) {
    }

    public static function for(mixed $page = null): self
    {
        return new self($page);
    }

    /**
     * @return array{
     *     currentLang: string,
     *     currentLocalization: Localization|null,
     *     isListRecords: bool,
     *     items: list<array{
     *         localization: Localization,
     *         targetUrl: string,
     *         hasTranslation: bool,
     *         isRecordSoftDeleted: bool,
     *         translationStatus: string|null
     *     }>
     * }
     */
    public function toArray(): array
    {
        $defaultLocalization = Localization::defaultLocalization();
        $defaultLang = $defaultLocalization?->language?->alpha2 ?? (string) config('app.locale');

        $pageLang = is_object($this->page) && isset($this->page->lang) ? $this->page->lang : null;
        $currentLang = $pageLang
            ?? request()->get('lang')
            ?? $defaultLang;

        if (! is_string($currentLang) || $currentLang === '') {
            $currentLang = $defaultLang;
        }

        $currentLocalization = Localization::query()
            ->with('language')
            ->where('locale_variant', $currentLang)
            ->where('is_active_admin', true)
            ->first();

        if (! $currentLocalization) {
            $currentLocalization = Localization::query()
                ->with('language')
                ->whereHas('language', fn ($q) => $q->where('alpha2', $currentLang))
                ->where('is_active_admin', true)
                ->first();
        }

        $isAdminContext = request()->is('admin/*')
            || request()->is('filament/*')
            || (is_object($this->page) && method_exists($this->page, 'getResource'));

        $translationUsesSoftDeletes = false;
        $shouldFilterLanguages = false;
        $record = $this->record();

        if (
            is_object($this->page)
            && ! ($this->page instanceof ListRecords)
            && $record !== null
            && method_exists($record, 'translations')
        ) {
            $translationModel = $record->translations()->getRelated();
            $translationUsesSoftDeletes = in_array(
                SoftDeletes::class,
                class_uses_recursive($translationModel),
                true
            );

            if ($this->page instanceof ViewRecord) {
                $allTranslations = $translationUsesSoftDeletes
                    ? $record->translations()->withTrashed()->get()
                    : $record->translations()->get();

                $shouldFilterLanguages = $allTranslations->isNotEmpty()
                    && $allTranslations->every(
                        fn ($trans): bool => $translationUsesSoftDeletes && $trans->trashed()
                    );
            }
        }

        /** @var Collection<int, Localization> $allLocalizations */
        $allLocalizations = Localization::query()
            ->with('language')
            ->when($isAdminContext, fn ($query) => $query->where('is_active_admin', true))
            ->when(! $isAdminContext, fn ($query) => $query->where('is_active_frontend', true))
            ->when($shouldFilterLanguages && $record !== null, function ($query) use ($translationUsesSoftDeletes, $record): void {
                $query->whereHas('language', function ($q) use ($translationUsesSoftDeletes, $record): void {
                    $translationsQuery = $translationUsesSoftDeletes
                        ? $record->translations()->withTrashed()
                        : $record->translations();
                    $q->whereIn('alpha2', $translationsQuery->pluck('locale'));
                });
            })
            ->orderBy('language_id')
            ->orderBy('locale_variant')
            ->get();

        $isListRecords = $this->page instanceof ListRecords;
        $items = [];

        foreach ($allLocalizations as $locale) {
            if ($locale->locale_variant === $currentLang) {
                continue;
            }

            $items[] = $this->itemForLocale(
                $locale,
                $translationUsesSoftDeletes,
                $isListRecords,
            );
        }

        return [
            'currentLang' => $currentLang,
            'currentLocalization' => $currentLocalization,
            'isListRecords' => $isListRecords,
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     localization: Localization,
     *     targetUrl: string,
     *     hasTranslation: bool,
     *     isRecordSoftDeleted: bool,
     *     translationStatus: string|null
     * }
     */
    private function itemForLocale(
        Localization $locale,
        bool $translationUsesSoftDeletes,
        bool $isListRecords,
    ): array {
        $targetUrl = request()->url().'?'.http_build_query(array_merge(
            request()->query(),
            ['lang' => $locale->locale_variant]
        ));
        $hasTranslation = true;
        $isRecordSoftDeleted = false;
        $isDeleted = false;
        $translationStatus = null;
        $record = $this->record();

        if ($isListRecords) {
            $hasTranslation = true;
        } elseif (
            ($this->page instanceof ViewRecord
                || $this->page instanceof EditRecord
                || $this->page instanceof CreateRecord)
            && $record !== null
            && method_exists($record, 'translations')
            && is_object($this->page)
            && method_exists($this->page, 'getResource')
        ) {
            $allTranslations = $translationUsesSoftDeletes
                ? $record->translations()->withTrashed()->get()
                : $record->translations()->get();
            $allTranslationsDeleted = $translationUsesSoftDeletes
                && $allTranslations->isNotEmpty()
                && $allTranslations->every(fn ($trans): bool => $trans->trashed());

            if ($allTranslationsDeleted) {
                $hasTranslation = $translationUsesSoftDeletes
                    && $record->translations()->withTrashed()
                        ->where('locale', $locale->locale_variant)
                        ->exists();
            } else {
                $translation = $record->translations()
                    ->where('locale', $locale->locale_variant)
                    ->first();
                $deletedTranslation = $translationUsesSoftDeletes
                    ? $record->translations()->withTrashed()
                        ->where('locale', $locale->locale_variant)
                        ->whereNotNull('deleted_at')
                        ->first()
                    : null;

                $hasTranslation = $translation !== null;
                $isDeleted = $deletedTranslation !== null && $translation === null;
            }

            $resource = $this->page->getResource();

            if ($this->page instanceof ViewRecord) {
                $targetUrl = $hasTranslation
                    ? $resource::getUrl('view', ['record' => $record, 'lang' => $locale->locale_variant])
                    : $resource::getUrl('edit', ['record' => $record, 'lang' => $locale->locale_variant]);
            } else {
                $targetUrl = $resource::getUrl('edit', [
                    'record' => $record,
                    'lang' => $locale->locale_variant,
                ]);
            }
        }

        if (! $hasTranslation && ! $isRecordSoftDeleted) {
            $translationStatus = $isDeleted ? 'deleted' : 'missing';
        }

        return [
            'localization' => $locale,
            'targetUrl' => $targetUrl,
            'hasTranslation' => $hasTranslation,
            'isRecordSoftDeleted' => $isRecordSoftDeleted,
            'translationStatus' => $translationStatus,
        ];
    }

    private function record(): ?Model
    {
        if (! is_object($this->page) || ! isset($this->page->record)) {
            return null;
        }

        $record = $this->page->record;

        return $record instanceof Model ? $record : null;
    }
}
