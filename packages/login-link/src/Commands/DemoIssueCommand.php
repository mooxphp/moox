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

class DemoIssueCommand extends Command
{
    protected $signature = 'login-link:demo
        {process=demo-dump : Process slug (demo-dump|demo-campaign)}
        {--email=demo@example.com : Recipient email}
        {--payload= : JSON payload, e.g. {"campaign":"spring"}}';

    protected $description = 'Issue a public demo signed link and print the consume URL (dump mail + dump redeem views)';

    public function handle(LoginLinkService $service): int
    {
        (new LoginLinkProcessSeeder)->run();

        $processSlug = (string) $this->argument('process');
        $process = LoginLinkProcess::query()->where('slug', $processSlug)->first();

        if ($process === null) {
            $this->error("Unknown process [{$processSlug}]. Seeded demos: demo-dump, demo-campaign.");

            return self::FAILURE;
        }

        if ($process->context !== LinkProcessContext::PUBLIC) {
            $this->error("Process [{$processSlug}] is not public-context; demo command only issues public links.");

            return self::FAILURE;
        }

        $payload = null;
        $payloadOption = $this->option('payload');

        if (is_string($payloadOption) && $payloadOption !== '') {
            $decoded = json_decode($payloadOption, true);

            if (! is_array($decoded)) {
                $this->error('--payload must be valid JSON object.');

                return self::FAILURE;
            }

            $payload = $decoded;
        } else {
            $payload = [
                'demo' => true,
                'process' => $processSlug,
                'issued_via' => 'login-link:demo',
            ];
        }

        $email = (string) $this->option('email');

        // Use the process row itself as subject so no domain model is required.
        $link = $service->issue(
            processSlug: $processSlug,
            subject: $process,
            email: $email,
            panelId: null,
            request: Request::create('/', 'POST', server: [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'login-link:demo',
            ]),
            payload: $payload,
        );

        $expiresMinutes = $process->resolveExpiryMinutes();
        $url = URL::temporarySignedRoute(
            'login-link.public.consume',
            now()->addMinutes($expiresMinutes),
            ['loginLink' => $link->getKey()],
        );

        $this->info('Demo link issued.');
        $this->line("process : {$processSlug} (context={$process->context}, handler={$process->handler_key}, template={$process->template_key})");
        $this->line("link id : {$link->getKey()}");
        $this->line("email   : {$email} (queued if a worker is running)");
        $this->line('payload : '.json_encode($payload, JSON_UNESCAPED_SLASHES));
        $this->newLine();
        $this->line('Open this URL in the browser:');
        $this->line($url);
        $this->newLine();
        $this->line('Dump page (after click): '.url('/login-link/demo/dump'));

        return self::SUCCESS;
    }
}
