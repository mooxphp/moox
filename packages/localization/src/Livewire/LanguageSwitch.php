<?php

declare(strict_types=1);

namespace Moox\Localization\Livewire;

use Illuminate\Support\Collection;
use Livewire\Component;
use Moox\Localization\Support\LocaleSwitcher;

class LanguageSwitch extends Component
{
    public string $locale = '';

    public string $context = 'frontend';

    public function mount(string $context = 'frontend'): void
    {
        $this->context = in_array($context, ['frontend', 'backend'], true)
            ? $context
            : 'frontend';

        $sessionLocale = session('locale');
        $this->locale = is_string($sessionLocale) ? $sessionLocale : '';
    }

    public function changeLocale(string $locale): mixed
    {
        if (! LocaleSwitcher::isAllowedLanguageCode($locale, $this->context)) {
            return null;
        }

        session()->put('locale', $locale);
        cookie()->queue(cookie()->forever('switch_locale', $locale));
        app()->setLocale($locale);

        $this->locale = $locale;

        return redirect(LocaleSwitcher::safeRedirectUrl(request()->header('Referer')));
    }

    /**
     * @return Collection<int, string>
     */
    public function getAvailableLocalesProperty()
    {
        return LocaleSwitcher::allowedLanguageCodes($this->context);
    }

    public function render()
    {
        return view('localization::livewire.language-switch', [
            'availableLocales' => $this->availableLocales,
        ]);
    }
}
