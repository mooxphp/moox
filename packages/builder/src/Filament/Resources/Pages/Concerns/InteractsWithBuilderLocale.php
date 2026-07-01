<?php

declare(strict_types=1);

namespace Moox\Builder\Filament\Resources\Pages\Concerns;

use Filament\Actions\Action;
use Illuminate\Support\Facades\Schema;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Localization\Models\Localization;

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
        if ($this->lang === '' || ! class_exists(Localization::class) || ! Schema::hasTable('localizations')) {
            return;
        }

        $isAllowed = Localization::query()
            ->where('locale_variant', $this->lang)
            ->where('is_active_admin', true)
            ->exists();

        if ($isAllowed) {
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

        return Action::make('language_selector')
            ->label($this->lang !== '' ? $this->lang : app(BuilderLocaleResolver::class)->adminDefaultLocale())
            ->disabled();
    }
}
