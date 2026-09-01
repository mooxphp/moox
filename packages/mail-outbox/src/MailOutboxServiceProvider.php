<?php

declare(strict_types=1);

namespace Moox\MailOutbox;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Moox\Core\MooxServiceProvider;
use Moox\MailOutbox\Commands\SendTestMailCommand;
use Moox\MailOutbox\Contracts\ProviderMessageIdReader;
use Moox\MailOutbox\Listeners\ApplyTestModeListener;
use Moox\MailOutbox\Listeners\RecordSentMailListener;
use Moox\MailOutbox\Support\CorrelationIdGenerator;
use Moox\MailOutbox\Support\MailableInspector;
use Moox\MailOutbox\Support\MailableRecipientFilter;
use Moox\MailOutbox\Support\MailFailureClassifier;
use Moox\MailOutbox\Support\MailOutboxConfig;
use Moox\MailOutbox\Support\MessageSizeGuard;
use Moox\MailOutbox\Support\OutboundMessagePreparer;
use Moox\MailOutbox\Support\ResendMailService;
use Moox\MailOutbox\Support\SymfonySentMessageProviderIdReader;
use Moox\MailOutbox\Support\TestModeMessageTransformer;
use Moox\MailOutbox\Support\TestModeRecipientMatcher;
use Moox\MailOutbox\Support\TestModeRecipientPlanner;
use Moox\MailOutbox\Support\TestModeSendCoordinator;
use Moox\MailOutbox\Support\TestModeSubjectPrefixer;
use Spatie\LaravelPackageTools\Package;

class MailOutboxServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('mail-outbox')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasMigrations([
                'create_mail_send_logs_table',
            ])
            ->hasCommand(SendTestMailCommand::class);

        $this->getMooxPackage()
            ->title('Moox Mail Outbox')
            ->released(false)
            ->stability('dev')
            ->category('mail')
            ->usedFor([
                'Queued outbound mail with send logging, size guard, retry classification, and correlation identifiers',
            ]);
    }

    public function packageBooted(): void
    {
        Event::listen(MessageSending::class, ApplyTestModeListener::class);
        Event::listen(MessageSent::class, RecordSentMailListener::class);

        $config = $this->app->make(MailOutboxConfig::class);

        if (
            $config->isTestModeEnabled()
            && $config->shouldWarnTestModeInProduction()
            && $this->app->environment('production')
        ) {
            Log::warning('Moox Mail Outbox test mode is enabled in production. Non-allowlisted recipients are redirected and logged as suppressed — not delivered.');
        }
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(MailOutboxConfig::class);
        $this->app->singleton(MessageSizeGuard::class);
        $this->app->singleton(MailFailureClassifier::class);
        $this->app->singleton(MailableInspector::class);
        $this->app->singleton(CorrelationIdGenerator::class);
        $this->app->singleton(ProviderMessageIdReader::class, SymfonySentMessageProviderIdReader::class);
        $this->app->singleton(ResendMailService::class);
        $this->app->singleton(TestModeRecipientMatcher::class);
        $this->app->singleton(TestModeRecipientPlanner::class);
        $this->app->singleton(TestModeSubjectPrefixer::class);
        $this->app->singleton(MailableRecipientFilter::class);
        $this->app->singleton(OutboundMessagePreparer::class);
        $this->app->singleton(TestModeMessageTransformer::class);
        $this->app->singleton(TestModeSendCoordinator::class);
    }
}
