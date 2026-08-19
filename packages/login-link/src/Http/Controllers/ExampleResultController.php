<?php

declare(strict_types=1);

namespace Moox\LoginLink\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Moox\LoginLink\Handlers\MassMailRedemptionHandler;
use Moox\LoginLink\Handlers\VerifyEmailRedemptionHandler;

class ExampleResultController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const MAIL_VIEWS = [
        'login' => 'login-link::mail.login-link',
        'verify-email' => 'login-link::mail.verify-email',
        'mass-mail' => 'login-link::mail.mass-mail',
    ];

    public function index(): View
    {
        return view('login-link::examples.index');
    }

    public function mail(string $template): View
    {
        if (! isset(self::MAIL_VIEWS[$template])) {
            abort(404);
        }

        return view(self::MAIL_VIEWS[$template], $this->previewData($template));
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
    private function previewData(string $template): array
    {
        $loginLink = (object) [
            'email' => 'demo@example.com',
        ];

        return [
            'title' => match ($template) {
                'login' => 'Passwordless login',
                'verify-email' => 'Email verification',
                default => 'Spring newsletter',
            },
            'content' => null,
            'url' => url('/login-link/examples'),
            'expiresMinutes' => 60,
            'user' => (object) [
                'name' => 'Alex Example',
                'first_name' => 'Alex',
                'last_name' => 'Example',
            ],
            'subject' => null,
            'payload' => $template === 'mass-mail'
                ? ['campaign' => 'Spring newsletter', 'mailing_id' => 'demo-001']
                : ['purpose' => 'email-verification'],
            'logoUrl' => null,
            'process' => null,
            'loginLink' => $loginLink,
        ];
    }
}
