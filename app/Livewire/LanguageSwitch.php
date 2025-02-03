<?php

namespace App\Livewire;

use Livewire\Component;

class LanguageSwitch extends Component
{

    public $locale;

    public function mount()
    {
        // Standardwert aus der aktuellen Session
        $this->locale = session('locale', config('app.locale'));
    }

    public function changeLocale($locale)
    {

        session(['locale' => $locale]);

        app()->setLocale($locale);

        return redirect(request()->header('Referer') ?? '/');
    }

    public function render()
    {
        return view('livewire.language-switch',[
            'availableLocales' => config('locale-switcher.locales', ['en' => 'English', 'de' => 'Deutsch']),
        ]);
    }
}
