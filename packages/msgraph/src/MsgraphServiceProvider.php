<?php

declare(strict_types=1);

namespace Moox\MsGraph;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Mail;
use Moox\Core\MooxServiceProvider;
use Moox\MailInbox\InboxDriverManager;
use Moox\MsGraph\Auth\ConnectionRegistry;
use Moox\MsGraph\Auth\GraphClientFactory;
use Moox\MsGraph\Mail\GraphInboxDriver;
use Moox\MsGraph\Mail\MailSettings;
use Moox\MsGraph\Mail\Transport\GraphHeaderSanitizingTransport;
use Spatie\LaravelPackageTools\Package;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Bridge\MicrosoftGraph\Transport\MicrosoftGraphTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

class MsgraphServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('msgraph')
            ->hasConfigFile();

        $this->getMooxPackage()
            ->title('Moox Msgraph')
            ->released(false)
            ->stability('dev')
            ->category('integration')
            ->usedFor([
                'Microsoft Graph API integration with connection registry, client factory, and Graph inbox driver',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ConnectionRegistry::class, function (Application $app): ConnectionRegistry {
            return new ConnectionRegistry(
                $app['config']->get('msgraph.connections', []),
                $app['config']->get('msgraph.default', 'default'),
            );
        });

        $this->app->singleton(GraphClientFactory::class, function (Application $app): GraphClientFactory {
            return new GraphClientFactory($app->make(ConnectionRegistry::class));
        });
    }

    public function packageBooted(): void
    {
        $this->registerGraphMailTransport();

        if (! $this->app->bound(InboxDriverManager::class)) {
            return;
        }

        $this->app->make(InboxDriverManager::class)->register(
            'msgraph',
            function (array $config) {
                $connection = is_string($config['connection'] ?? null) && $config['connection'] !== ''
                    ? $config['connection']
                    : 'default';
                $address = is_string($config['mailbox_address'] ?? null)
                    ? $config['mailbox_address']
                    : '';

                $client = $this->app->make(GraphClientFactory::class)->make($connection);

                return GraphInboxDriver::make(
                    $client,
                    $address,
                    MailSettings::fromConfig(),
                );
            },
        );
    }

    /**
     * Register the Symfony Microsoft Graph mailer bridge as the 'microsoftgraph'
     * transport and expose a default 'msgraph' mailer.
     *
     * The DSN is built from the connection registry so Azure AD tenant
     * credentials live only in config/msgraph.php. A host may predefine
     * mail.mailers.msgraph to override the connection or add options.
     */
    private function registerGraphMailTransport(): void
    {
        Mail::extend('microsoftgraph', function (array $config): TransportInterface {
            $connection = $this->app->make(ConnectionRegistry::class)->get(
                is_string($config['connection'] ?? null) && $config['connection'] !== ''
                    ? $config['connection']
                    : null,
            );

            $dsn = new Dsn(
                scheme: 'microsoftgraph+api',
                host: 'default',
                user: $connection->clientId,
                password: $connection->clientSecret,
                options: ['tenantId' => $connection->tenantId],
            );

            $factory = new MicrosoftGraphTransportFactory(
                dispatcher: null,
                client: HttpClient::create(),
            );

            return new GraphHeaderSanitizingTransport($factory->create($dsn));
        });

        if ($this->app['config']->get('mail.mailers.msgraph') === null) {
            $this->app['config']->set('mail.mailers.msgraph', [
                'transport' => 'microsoftgraph',
                'connection' => $this->app['config']->get('msgraph.default', 'default'),
            ]);
        }
    }
}
