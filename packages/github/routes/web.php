<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

// TODO scopes configurable in config file
// TODO route name should be configurable in config file
Route::middleware(['web'])->group(function () {
    Route::middleware(['auth'])->get('/auth/github/connect', function () {
        return Socialite::driver('github')->scopes(['read:org', 'repo'])->redirect();
    })->name('github.connect');

    Route::get('/auth/github/callback', function () {
        try {
            $githubUser = Socialite::driver('github')->scopes(['read:org', 'repo'])->user();
            $currentUser = Auth::user();

            if (! $currentUser) {
                return redirect('/moox/login')->with('error', 'Bitte zuerst anmelden');
            }

            $currentUser->update([
                'github_id' => $githubUser->getId(),
                'github_token' => $githubUser->token,
                'email_verified_at' => $currentUser->email_verified_at ?? now(),
            ]);

            $panel = Filament::getCurrentOrDefaultPanel();
            $redirectUrl = $panel ? $panel->getUrl() : '/moox';

            return redirect($redirectUrl)->with('success', 'GitHub erfolgreich verbunden!');
        } catch (\Exception $e) {
            \Log::error('GitHub OAuth Fehler: '.$e->getMessage());

            $panel = Filament::getCurrentOrDefaultPanel();
            $redirectUrl = $panel ? $panel->getUrl() : '/moox';

            return redirect($redirectUrl)->with('error', 'GitHub-Verbindung fehlgeschlagen. Bitte versuchen Sie es erneut.');
        }
    })->name('github.callback');

    Route::middleware(['auth'])->get('/auth/github/disconnect', function () {
        $currentUser = Auth::user();

        if ($currentUser) {
            $currentUser->update([
                'github_id' => null,
                'github_token' => null,
            ]);
        }

        $panel = Filament::getCurrentOrDefaultPanel();
        $redirectUrl = $panel ? $panel->getUrl() : '/moox';

        return redirect($redirectUrl)->with('success', 'GitHub-Verbindung entfernt.');
    })->name('github.disconnect');
});
