<?php

declare(strict_types=1);

namespace Moox\MailInbox;

use Illuminate\Console\Scheduling\Schedule;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Moox\Core\MooxServiceProvider;
use Moox\MailInbox\Commands\FetchMailCommand;
use Moox\MailInbox\Commands\PollMailCommand;
use Moox\MailInbox\Commands\ProcessMailCommand;
use Moox\MailInbox\Commands\StatusCommand;
use Moox\MailInbox\Services\GraphMailService;
use Moox\MailInbox\Services\MailInboxService;
use Moox\MailInbox\Support\MailInboxGraphServiceClientFactory;
use Spatie\LaravelPackageTools\Package;

class MailInboxServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('mail-inbox')
            ->hasConfigFile()
            ->hasMigrations([
                'create_inbox_messages_table',
                'create_inbox_attachments_table',
                'create_mail_inbox_sync_states_table',
            ])
            ->hasCommands([
                FetchMailCommand::class,
                ProcessMailCommand::class,
                PollMailCommand::class,
                StatusCommand::class,
            ]);

        $this->getMooxPackage()
            ->title('Moox MailInbox')
            ->released(false)
            ->stability('dev')
            ->category('billing')
            ->usedFor([
                'Microsoft Graph mailbox polling for inbound mail',
            ]);
    }

    public function register(): void
    {
        parent::register();

        if (config('logging.channels.mail-inbox') === null) {
            config()->set('logging.channels.mail-inbox', [
                'driver' => 'single',
                'path' => storage_path('logs/mail-inbox.log'),
                'level' => config('logging.channels.single.level', 'debug'),
                'replace_placeholders' => true,
            ]);
        }

        $this->app->singleton(GraphServiceClient::class, fn (): GraphServiceClient => MailInboxGraphServiceClientFactory::make(
            new ClientCredentialContext(
                (string) config('mail-inbox.graph.tenant_id'),
                (string) config('mail-inbox.graph.client_id'),
                (string) config('mail-inbox.graph.client_secret'),
            ),
        ));

        $this->app->singleton(GraphMailService::class, function ($app): GraphMailService {
            return new GraphMailService($app->make(GraphServiceClient::class));
        });

        $this->app->singleton(MailInboxService::class);
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $interval = max(1, min(59, (int) config('mail-inbox.poll_interval', 5)));

                $schedule->command('mail-inbox:poll')
                    ->cron("*/{$interval} * * * *")
                    ->withoutOverlapping()
                    ->runInBackground()
                    ->appendOutputTo(storage_path('logs/mail-inbox.log'));
            });
        }
    }
}
