<?php

declare(strict_types=1);

namespace Moox\UserDevice\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Resources\UserDeviceResource;
use Throwable;

class TrustDeviceController
{
    public function __invoke(Request $request, string $panel, int $device): RedirectResponse
    {
        $this->setFilamentPanel($panel);

        if (! filament()->auth()->check()) {
            session()->put('url.intended', $request->fullUrl());

            return redirect()->to($this->loginUrl($panel));
        }

        $authUser = filament()->auth()->user();

        $record = UserDevice::query()->findOrFail($device);

        if ($authUser->can('update', $record) !== true) {
            abort(403);
        }

        $record->update(['whitelisted' => true]);

        try {
            $home = filament()->getUrl();
            if (filled($home)) {
                return redirect()->to($home);
            }
        } catch (Throwable) {
            //
        }

        return redirect()->to($this->loginUrl($panel));
    }

    protected function setFilamentPanel(string $panelId): void
    {
        try {
            Filament::setCurrentPanel(Filament::getPanel($panelId));
        } catch (Throwable) {
            // Invalid panel id — leave default panel; auth/ownership checks still apply.
        }
    }

    protected function loginUrl(string $panelId): string
    {
        try {
            return filament()->getLoginUrl() ?? UserDeviceResource::getUrl('index', panel: $panelId);
        } catch (Throwable) {
            return UserDeviceResource::getUrl('index', panel: $panelId);
        }
    }
}
