<?php

namespace Moox\Media\Resources\MediaCollectionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Moox\Localization\Models\Localization;
use Moox\Media\Resources\MediaCollectionResource;

class ListMediaCollections extends ListRecords
{
    protected static string $resource = MediaCollectionResource::class;

    public string $lang;

    protected $queryString = [
        'lang' => ['except' => ''],
    ];

    public function mount(): void
    {
        parent::mount();
        $this->lang = request()->get('lang', $this->getDefaultLocale());

        app()->setLocale($this->lang);
    }

    protected function getDefaultLocale(): string
    {
        if (class_exists(Localization::class)) {
            $defaultLocale = Localization::query()
                ->where('is_default', true)
                ->where('is_active_admin', true)
                ->first();

            if ($defaultLocale) {
                return $defaultLocale->getAttribute('locale_variant') ?: $defaultLocale->language->alpha2;
            }

            $firstActiveLocale = Localization::query()
                ->where('is_active_admin', true)
                ->first();

            if ($firstActiveLocale) {
                return $firstActiveLocale->getAttribute('locale_variant') ?: $firstActiveLocale->language->alpha2;
            }
        }

        return app()->getLocale();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data, string $model): Model => $model::create($data))
                ->url(fn (): string => static::$resource::getUrl('create', ['lang' => $this->lang])),
        ];
    }

    public function getTableQuery(): Builder|Relation|null
    {
        return parent::getTableQuery()
            ->whereHas('translations'); // Zeige alle Collections die irgendeine Übersetzung haben
    }

    public function changeLanguage(string $lang): void
    {
        $this->lang = $lang;
        $url = static::$resource::getUrl('index', ['lang' => $lang]);
        $this->redirect($url);
    }
}
