<?php

declare(strict_types=1);

namespace Moox\Tree\Livewire\Concerns;

use Moox\Tree\Support\TreeLocale;

trait ManagesTreeToolbar
{
    public string $search = '';

    public string $lang = '';

    public function changeLanguage(string $lang): void
    {
        $this->lang = $lang;
        TreeLocale::syncToRequest($this->lang);

        $resourceClass = $this->configuration()->getSourceResourceClass();

        if ($resourceClass !== null && method_exists($resourceClass, 'getUrl')) {
            $this->redirect($resourceClass::getUrl(
                'index',
                TreeLocale::languageChangeParameters($lang),
            ));
        }
    }

    protected function mountTreeToolbar(string $search = '', string $lang = ''): void
    {
        if ($search !== '') {
            $this->search = $search;
        } elseif ($this->search === '' && $this->usesStandaloneToolbarSearch()) {
            $this->search = (string) request()->input('search', request()->input('tableSearch', ''));
        }

        if ($lang !== '') {
            $this->lang = $lang;
        } elseif ($this->lang === '') {
            $this->lang = (string) request()->input('lang', TreeLocale::resolveDefaultLocale());
        }

        TreeLocale::syncToRequest($this->lang);
    }

    protected function hydrateTreeToolbar(): void
    {
        TreeLocale::syncToRequest($this->lang);
    }

    protected function shouldApplySearchToTreeQuery(): bool
    {
        $configuration = $this->configuration();

        if ($configuration->usesFilamentTableToolbar()) {
            return true;
        }

        return $this->usesStandaloneToolbarSearch();
    }

    protected function usesStandaloneToolbarSearch(): bool
    {
        return $this->configuration()->getSourceResourceClass() === null
            || $this->configuration()->isToolbarSearchEnabled();
    }
}
