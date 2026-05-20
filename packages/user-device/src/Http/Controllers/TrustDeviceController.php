<?php

declare(strict_types=1);

namespace Moox\UserDevice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Resources\UserDeviceResource;

class TrustDeviceController
{
    public function __invoke(Request $request, string $panel, int $device): RedirectResponse
    {
        if (! filament()->auth()->check()) {
            session()->put('url.intended', $request->fullUrl());

            return redirect()->to(UserDeviceResource::getUrl('index', panel: $panel));
        }

        $authUser = filament()->auth()->user();

        $record = UserDevice::query()->findOrFail($device);

        if ($authUser->can('update', $record) !== true) {
            abort(403);
        }

        $record->update(['whitelisted' => true]);

        return redirect()->to(UserDeviceResource::getUrl('index', panel: $panel));
    }
}
