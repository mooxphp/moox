<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Moox\MailOutbox\Jobs\SendMailJob;
use Moox\MailOutbox\Mail\OutboxTestMail;
use Moox\MailOutbox\Models\MailSendLog;
use Throwable;

/**
 * Sends a probe mail through SendMailJob and prints the resulting send-log row.
 * Transport-agnostic: pass any configured mailer via --mailer.
 *
 * Real send:  php artisan mail-outbox:test-send --to=real@customer.tld --mailer=msgraph
 * Test mode:  php artisan mail-outbox:test-send --to=real@customer.tld --mailer=msgraph --test
 */
class SendTestMailCommand extends Command
{
    protected $signature = 'mail-outbox:test-send
        {--to= : Intended recipient address}
        {--mailer= : Named Laravel mailer (defaults to mail.default)}
        {--test : Route through safe test mode (redirect + suppressed status)}
        {--redirect= : Sandbox address for test mode (overrides config)}';

    protected $description = 'Send a probe mail through the outbox, with or without safe test mode';

    public function handle(): int
    {
        $to = trim((string) $this->option('to'));

        if ($to === '') {
            $this->error('Missing --to=<address>.');

            return self::FAILURE;
        }

        $mailer = trim((string) $this->option('mailer')) ?: (string) config('mail.default');

        // --test forces test mode on for this run. Without it, the ambient
        // config is honoured — so this command can verify a global
        // MAIL_OUTBOX_TEST_MODE=true from the environment.
        if ($this->option('test')) {
            $redirect = trim((string) $this->option('redirect'))
                ?: (string) config('mail-outbox.test_mode.redirect_to');

            if ($redirect === '') {
                $this->error('Test mode requested but no sandbox address. Set mail-outbox.test_mode.redirect_to or pass --redirect=.');

                return self::FAILURE;
            }

            Config::set('mail-outbox.test_mode.enabled', true);
            Config::set('mail-outbox.test_mode.redirect_to', $redirect);
        }

        $testModeActive = (bool) config('mail-outbox.test_mode.enabled');

        if ($testModeActive) {
            $this->warn(sprintf(
                "Test mode ACTIVE — mail for %s is redirected to %s and logged as 'suppressed'.",
                $to,
                (string) config('mail-outbox.test_mode.redirect_to'),
            ));
        } else {
            $this->warn("Test mode OFF — mail is delivered for real to {$to}.");
        }

        $this->line('From:   '.(string) config('mail.from.address'));
        $this->line('Mailer: '.$mailer);

        try {
            SendMailJob::dispatchSync(new OutboxTestMail($to, $testModeActive), $mailer);
        } catch (Throwable $e) {
            $this->error('Send threw: '.$e->getMessage());

            return self::FAILURE;
        }

        $log = MailSendLog::query()->latest('id')->first();

        if ($log === null) {
            $this->error('No mail_send_logs row was written. Has the migration run?');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Send log row:');
        $this->table(['field', 'value'], [
            ['id', (string) $log->id],
            ['status', $log->status->value ?? (string) $log->status],
            ['mailer', (string) $log->mailer],
            ['intended', (string) json_encode($log->intended_recipients)],
            ['actual', (string) json_encode($log->actual_recipients)],
            ['message_id', (string) $log->message_id],
            ['error', (string) $log->error],
        ]);

        return $log->error === null ? self::SUCCESS : self::FAILURE;
    }
}
