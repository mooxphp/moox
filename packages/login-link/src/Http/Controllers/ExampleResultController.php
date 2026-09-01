<?php

declare(strict_types=1);

namespace Moox\LoginLink\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Moox\LoginLink\Handlers\MassMailRedemptionHandler;
use Moox\LoginLink\Handlers\VerifyEmailRedemptionHandler;
use Moox\LoginLink\Mail\ProcessLinkMail;

class ExampleResultController extends Controller
{
    public function index(): View
    {
        return view('login-link::examples.index');
    }

    public function mail(): View
    {
        return view(ProcessLinkMail::DEMO_VIEW, $this->previewData());
    }

    public function unavailable(string $reason = 'expired'): View
    {
        $allowed = ['used', 'expired', 'invalid'];

        if (! in_array($reason, $allowed, true)) {
            $reason = 'expired';
        }

        return view(PublicLoginLinkRedemptionController::DEMO_VIEW, [
            'reason' => $reason,
            'supportName' => 'Acme Support',
            'supportEmail' => 'help@example.com',
            'supportPhone' => '+49 1234',
        ]);
    }

    public function emailVerified(Request $request): View
    {
        return view('login-link::examples.email-verified', [
            'result' => $request->session()->get(VerifyEmailRedemptionHandler::SESSION_KEY),
        ]);
    }

    public function mailingConfirmed(Request $request): View
    {
        return view('login-link::examples.mailing-confirmed', [
            'result' => $request->session()->get(MassMailRedemptionHandler::SESSION_KEY),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function previewData(): array
    {
        return [
            'title' => 'Passwordless login',
            'content' => null,
            'url' => url('/login-link/examples'),
            'expiresMinutes' => 60,
            'user' => (object) [
                'name' => 'Alex Example',
                'first_name' => 'Alex',
                'last_name' => 'Example',
            ],
            'subject' => null,
            'payload' => [],
            'logoUrl' => null,
            'process' => null,
            'loginLink' => (object) [
                'email' => 'demo@example.com',
            ],
        ];
    }
}
