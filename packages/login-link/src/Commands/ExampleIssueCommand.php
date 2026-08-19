<?php

declare(strict_types=1);

namespace Moox\LoginLink\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Moox\LoginLink\Database\Seeders\LoginLinkProcessSeeder;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Services\LoginLinkService;
use Moox\LoginLink\Support\LinkProcessContext;

class ExampleIssueCommand extends Command
{
    protected $signature = 'login-link:example
        {process=verify-email : Process slug (verify-email|mass-mail)}
        {--email=demo@example.com : Recipient email}
        {--payload= : JSON payload, e.g. {"campaign":"Spring newsletter"}}';

    protected $description = 'Issue a public example signed link (email verification or mass-mail confirmation)';

    public function handle(LoginLinkService $service): int
    {
        (new LoginLinkProcessSeeder)->run();

        $processSlug = (string) $this->argument('process');
        $process = LoginLinkProcess::query()->where('slug', $processSlug)->first();

        if ($process === null) {
            $this->error("Unknown process [{$processSlug}]. Example slugs: verify-email, mass-mail.");

            return self::FAILURE;
        }

        if ($process->context !== LinkProcessContext::PUBLIC) {
            $this->error("Process [{$processSlug}] is not public-context. Use the Filament login form for the login example.");

            return self::FAILURE;
        }

        $payload = $this->resolvePayload($processSlug);

        if ($payload === false) {
            return self::FAILURE;
        }

        $email = (string) $this->option('email');

        $link = $service->issue(
            processSlug: $processSlug,
            subject: $process,
            email: $email,
            panelId: null,
            request: Request::create('/', 'POST', server: [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'login-link:example',
            ]),
            payload: $payload,
            queueMail: false,
        );

        $expiresMinutes = $process->resolveExpiryMinutes();
        $url = URL::temporarySignedRoute(
            'login-link.public.consume',
            now()->addMinutes($expiresMinutes),
            ['loginLink' => $link->getKey()],
        );

        $this->info('Example link issued.');
        $this->line("process : {$processSlug} (context={$process->context}, handler={$process->handler_key}, template={$process->template_key})");
        $this->line("link id : {$link->getKey()}");
        $this->line("email   : {$email} (sent immediately, not queued)");
        $this->line('payload : '.json_encode($payload, JSON_UNESCAPED_SLASHES));
        $this->newLine();
        $this->line('Open the signed link:');
        $this->line($url);
        $this->newLine();
        $this->line('Preview this email in the browser:');
        $this->line(url('/login-link/examples/mail/'.$processSlug));
        $this->line('All example mails: '.url('/login-link/examples'));

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>|false
     */
    private function resolvePayload(string $processSlug): array|false
    {
        $payloadOption = $this->option('payload');

        if (is_string($payloadOption) && $payloadOption !== '') {
            $decoded = json_decode($payloadOption, true);

            if (! is_array($decoded)) {
                $this->error('--payload must be a valid JSON object.');

                return false;
            }

            return $decoded;
        }

        if ($processSlug === 'mass-mail') {
            return [
                'campaign' => 'Spring newsletter',
                'mailing_id' => 'demo-001',
            ];
        }

        return [
            'purpose' => 'email-verification',
        ];
    }
}
