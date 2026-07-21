<?php

declare(strict_types=1);

namespace Moox\Builder\Filament\Resources\Pages\Concerns;

use Filament\Actions\Action;
use Moox\Builder\Support\BuilderAdminLocalizationCatalog;
use Moox\Builder\Support\BuilderLocaleResolver;

trait InteractsWithBuilderLocale
{
    public string $lang = '';

    public function hydrateInteractsWithBuilderLocale(): void
    {
        $this->syncLangToRequest();
    }

    public function mountInteractsWithBuilderLocale(): void
    {
        $this->lang = request()->query(
            'lang',
            request()->input('lang', app(BuilderLocaleResolver::class)->adminDefaultLocale()),
        );

        $this->syncLangToRequest();
    }

    public function syncLangToRequest(): void
    {
        if ($this->lang !== '') {
            request()->merge(['lang' => $this->lang]);
        }
    }

    protected function guardBuilderAdminLocale(): void
    {
        $catalog = app(BuilderAdminLocalizationCatalog::class);

        if ($this->lang === '') {
            return;
        }

        if ($catalog->isAllowedAdminLocale($this->lang)) {
            return;
        }

        if (! method_exists($this, 'getResource')) {
            return;
        }

        $default = app(BuilderLocaleResolver::class)->adminDefaultLocale();

        if (method_exists($this, 'getRecord') && $this->getRecord() !== null) {
            $this->redirect(static::getResource()::getUrl('edit', [
                'record' => $this->getRecord(),
                'lang' => $default,
            ]));

            return;
        }

        $this->redirect(static::getResource()::getUrl('index', ['lang' => $default]));
    }

    protected function getBuilderLanguageSelectorAction(): Action
    {
        if (view()->exists('localization::lang-selector')) {
            return Action::make('language_selector')
                ->view('localization::lang-selector');
        }

        if (view()->exists('builder::lang-selector')) {
            return Action::make('language_selector')
                ->view('builder::lang-selector');
        }

        return Action::make('language_selector')
            ->label($this->lang !== '' ? $this->lang : app(BuilderLocaleResolver::class)->adminDefaultLocale())
            ->disabled();
    }
}
