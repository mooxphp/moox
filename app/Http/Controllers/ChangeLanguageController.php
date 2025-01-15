<?php

namespace App\Http\Controllers;

use App\Locale\Models\StaticLocale;

class ChangeLanguageController extends Controller
{
    public function __invoke($locale)
    {
        $locale = StaticLocale::findOrFaile($locale);
        if ($locale) {
            session()->put('locale', $locale->language->alpha3_b);

            return redirect()->route('home')->with('status', 'Language changed to '.$locale);
        } else {
            abort(404);
        }

        return redirect()->back();
    }
}
