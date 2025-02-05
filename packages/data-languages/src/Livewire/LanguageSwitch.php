<?php

namespace Moox\DataLanguages\Livewire;

use Livewire\Component;
use Moox\DataLanguages\Models\Localization;

class LanguageSwitch extends Component
{

    public $locale;
    public $context;

    public function mount(string $context = 'frontend')
    {
        $this->context = $context;

        $this->locale = session('locale');
    }

    public function changeLocale($locale)
    {

        session()->put('locale', $locale);

        cookie()->queue(cookie()->forever('switch_locale', $locale));

        app()->setLocale($locale);

        return redirect(request()->header('Referer') ?? '/');
    }

    public function getAvailableLocalesProperty()
    {
        return Localization::query()
            ->when($this->context === 'backend', function ($query) {
                $query->where('is_active_admin', true);
            })
            ->when($this->context === 'frontend', function ($query) {
                $query->where('is_active_frontend', true);
            })
            ->get()
            ->pluck('language.alpha2', 'language_id');
    }

    public function render()
    {
        return view('data-languages::livewire.language-switch',[
            'availableLocales' => $this->availableLocales,
        ]);
    }
}
