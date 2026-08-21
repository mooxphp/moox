<?php

declare(strict_types=1);

namespace Moox\MailOutbox;

use Moox\Core\MooxServiceProvider;
use Moox\MailOutbox\Contracts\ProviderMessageIdReader;
use Moox\MailOutbox\Support\CorrelationIdGenerator;
use Moox\MailOutbox\Support\MailableInspector;
use Moox\MailOutbox\Support\MailFailureClassifier;
use Moox\MailOutbox\Support\MailOutboxConfig;
use Moox\MailOutbox\Support\MessageSizeGuard;
use Moox\MailOutbox\Support\SymfonySentMessageProviderIdReader;
use Spatie\LaravelPackageTools\Package;

class MailOutboxServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('mail-outbox')
            ->hasConfigFile()
            ->hasMigrations([
                'create_mail_send_logs_table',
            ]);

        $this->getMooxPackage()
            ->title('Moox Mail Outbox')
            ->released(false)
            ->stability('dev')
            ->category('mail')
            ->usedFor([
                'Queued outbound mail with send logging, size guard, retry classification, and correlation identifiers',
            ]);
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
    }
}
