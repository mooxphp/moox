<?php

declare(strict_types=1);

namespace Moox\UserDevice\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Resources\UserDeviceResource;
use Moox\UserDevice\Support\UserDeviceAuthorization;
use Moox\UserDevice\Support\UserDevicePanel;
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

        // Magic-link self-trust: the device owner must be able to confirm without
        // Shield Update:UserDevice (fresh installs / roles often lack that permission).
        // Ops with update / ViewAll still pass via policy.
        $mayTrust = UserDeviceAuthorization::ownsRecord($authUser, $record)
            || $authUser->can('update', $record) === true;

        if (! $mayTrust) {
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
            $login = filament()->getLoginUrl();

            if (filled($login)) {
                return $login;
            }
        } catch (Throwable) {
            //
        }

        if (UserDevicePanel::registersResource($panelId)) {
            try {
                return UserDeviceResource::getUrl('index', panel: $panelId);
            } catch (Throwable) {
                //
            }
        }

        return url('/'.trim($panelId, '/').'/login');
    }
}
