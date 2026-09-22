<?php

declare(strict_types=1);

namespace Moox\MailTesting;

use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Event;
use Livewire\Finder\Finder;
use Livewire\Livewire;
use Moox\Core\MooxServiceProvider;
use Moox\MailTesting\Commands\RenderMailTestingCommand;
use Moox\MailTesting\Pages\MailTestingPage;
use Moox\MailTesting\Plugins\MailTestingPlugin;
use Moox\MailTesting\Resources\MailTestingMessageResource\Pages\ListMailTestingMessages;
use Moox\MailTesting\Resources\MailTestingMessageResource\Pages\ViewMailTestingMessage;
use Moox\MailTesting\Support\MailTestingWorkerStatus;
use Spatie\LaravelPackageTools\Package;

class MailTestingServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('mail-testing')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasMigrations([
                '01_create_mail_testing_runs_table',
                '02_create_mail_testing_messages_table',
            ])
            ->runsMigrations()
            ->hasCommands([
                RenderMailTestingCommand::class,
            ]);

        $this->getMooxPackage()
            ->title('Moox Mail Testing')
            ->released(false)
            ->stability('dev')
            ->category('development');
    }

    public function packageBooted(): void
    {
        Event::listen(Looping::class, function (Looping $event): void {
            MailTestingWorkerStatus::rememberIfListening((string) $event->queue);
        });
    }

    public function packageRegistered(): void
    {
        $this->app->afterResolving(PanelRegistry::class, function (PanelRegistry $registry): void {
            $this->registerAdminPlugin($registry);
        });
    }

    private function registerAdminPlugin(PanelRegistry $registry): void
    {
        $panel = $registry->get('admin');

        if (! $panel instanceof Panel || $panel->hasPlugin('mail-testing')) {
            return;
        }

        $panel->plugin(MailTestingPlugin::make());

        foreach ([
            MailTestingPage::class,
            ListMailTestingMessages::class,
            ViewMailTestingMessage::class,
        ] as $component) {
            [, $componentName] = app(Finder::class)->parseNamespaceAndName($component);
            Livewire::component($componentName, $component);
        }
    }
}
