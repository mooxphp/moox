<?php

declare(strict_types=1);

namespace Moox\Core\Entities\Items\Static\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\CanResolveResourceClass;
use Moox\Localization\Models\Localization;

abstract class BaseListStatic extends ListRecords
{
    use CanResolveResourceClass;

    public string $lang = '';

    protected $queryString = [
        'lang' => ['except' => ''],
    ];

    public function hydrate(): void
    {
        $this->syncLangToRequest();
    }

    public function mount(): void
    {
        parent::mount();

        $defaultLocalization = Localization::query()->where('is_default', true)->first();
        $defaultLang = $defaultLocalization->locale_variant ?? config('app.locale');

        $this->lang = request()->input('lang', $defaultLang);
        $this->syncLangToRequest();
    }

    protected function syncLangToRequest(): void
    {
        if ($this->lang !== '') {
            request()->merge(['lang' => $this->lang]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->hidden(fn (): bool => isset($this->activeTab) && $this->activeTab === 'deleted'),
        ];
    }

    public function changeLanguage(string $lang): void
    {
        $this->lang = $lang;
        $this->syncLangToRequest();

        $params = ['lang' => $lang];

        if (isset($this->activeTab)) {
            $params['tab'] = $this->activeTab;
        }

        $this->redirect($this->getResource()::getUrl('index', $params));
    }
}
