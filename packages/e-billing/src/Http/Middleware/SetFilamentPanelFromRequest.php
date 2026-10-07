<?php

declare(strict_types=1);

namespace Moox\EBilling\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Http\Request;

/**
 * Sets the Filament panel from the request, then runs Filament authentication.
 *
 * Document preview/download routes live outside panel route groups (plain web
 * routes embedded in an iframe). Panel context must be set in the same
 * middleware that authenticates: Laravel's middleware priority moves
 * {@see FilamentAuthenticate} (via its Illuminate parent) before sibling
 * middleware, so a separate "set panel" middleware would run too late.
 */
final class SetFilamentPanelFromRequest extends FilamentAuthenticate
{
    public const QUERY_KEY = 'panel';

    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        $this->setPanelFromRequest($request);

        parent::authenticate($request, $guards);
    }

    private function setPanelFromRequest(Request $request): void
    {
        $panelId = $request->query(self::QUERY_KEY);

        if (! is_string($panelId) || $panelId === '') {
            $panelId = $request->route(self::QUERY_KEY);
        }

        if (! is_string($panelId) || $panelId === '') {
            return;
        }

        $panel = Filament::getPanels()[$panelId] ?? null;

        if ($panel === null) {
            abort(404);
        }

        Filament::setCurrentPanel($panel);
    }
}
