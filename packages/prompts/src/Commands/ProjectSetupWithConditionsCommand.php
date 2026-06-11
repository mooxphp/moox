<?php

namespace Moox\Prompts\Commands;

use Illuminate\Support\Facades\Storage;
use Moox\Prompts\PromptsServiceProvider;
use Moox\Prompts\Support\FlowCommand;

use function Moox\Prompts\confirm;
use function Moox\Prompts\multiselect;
use function Moox\Prompts\select;
use function Moox\Prompts\text;

/**
 * Demo command: project setup wizard with conditional steps.
 *
 * Showcases FlowCommand patterns (conditions, multiselect, confirm, $this->call()).
 * Side effects (vendor:publish, file writes) are opt-in and default to off.
 */
class ProjectSetupWithConditionsCommand extends FlowCommand
{
    protected $signature = 'prompts:project-setup';

    protected $description = '[Demo] Project setup wizard with conditional steps (CLI & Web)';

    public ?string $environment = null;

    public array $features = [];

    public ?string $projectName = null;

    public ?string $webhookUrl = null;

    public ?bool $publishConfig = null;

    public function promptFlowSteps(): array
    {
        return [
            'stepIntro',
            'stepEnvironment',
            'stepFeatures',
            'stepProjectName',
            'stepPublishConfigConfirm',
            'stepPublishConfigExecute',
            'stepLoggingLevel',
            'stepWebhookUrl',
            'stepSummaryOverview',
            'stepSummaryConfirm',
            'stepOutro',
        ];
    }

    public function stepIntro(): void
    {
        $this->info('=== Demo: Projekt Setup (mit Conditions) ===');
        $this->warn('Nur ein Beispiel-Command — keine produktiven Änderungen ohne explizite Bestätigung.');
    }

    public function stepEnvironment(): void
    {
        $this->environment = select(
            label: 'Welche Umgebung konfigurierst du?',
            options: [
                'local' => 'Local',
                'staging' => 'Staging',
                'production' => 'Production',
            ],
            default: 'local',
        );

        $this->info("✅ Environment: {$this->environment}");
    }

    public function stepFeatures(): void
    {
        $this->features = multiselect(
            label: 'Welche Features sollen aktiviert werden?',
            options: [
                'logging' => 'Request Logging',
                'webhooks' => 'Webhooks',
                'metrics' => 'Metrics / Telemetry',
            ],
            required: false,
        );

        $this->info('✅ Features: '.(empty($this->features) ? 'keine' : implode(', ', $this->features)));
    }

    public function stepProjectName(): void
    {
        $this->projectName = text(
            label: 'Wie heißt dein Projekt?',
            placeholder: 'z.B. MyCoolApp',
            validate: 'required|min:3',
            required: true,
        );

        $this->info("✅ Projekt: {$this->projectName}");
    }

    public function stepPublishConfigConfirm(): void
    {
        $this->publishConfig = confirm(
            label: 'Demo: Moox Prompts Config veröffentlichen? (vendor:publish)',
            default: false,
        );

        $this->info('✅ Publish config: '.($this->publishConfig ? 'ja' : 'nein'));
    }

    public function stepPublishConfigExecute(): void
    {
        if (! $this->publishConfig) {
            $this->info('⚪ Config "moox-prompts-config" wurde übersprungen.');

            return;
        }

        $this->call('vendor:publish', [
            '--provider' => PromptsServiceProvider::class,
            '--tag' => 'moox-prompts-config',
        ]);

        $this->info('✅ Config "moox-prompts-config" wurde veröffentlicht.');
    }

    public function stepLoggingLevel(): void
    {
        if (! in_array('logging', $this->features, true)) {
            return;
        }

        $level = select(
            label: 'Welches Logging-Level möchtest du verwenden?',
            options: [
                'error' => 'Errors only',
                'info' => 'Info & Errors',
                'debug' => 'Debug (verbose)',
            ],
            default: 'info',
        );

        $this->info("✅ Logging-Level: {$level}");
    }

    public function stepWebhookUrl(): void
    {
        if (! in_array('webhooks', $this->features, true)) {
            return;
        }

        $this->webhookUrl = text(
            label: 'Webhook-URL (z.B. https://example.com/webhook)',
            placeholder: 'https://...',
            validate: 'required|url',
            required: true,
        );

        $this->info("✅ Webhook-URL: {$this->webhookUrl}");
    }

    public function stepSummaryOverview(): void
    {
        $this->info('--- Zusammenfassung ---');
        $this->line('Projekt: '.$this->projectName);
        $this->line('Environment: '.$this->environment);
        $this->line('Features: '.(empty($this->features) ? 'keine' : implode(', ', $this->features)));

        if (in_array('webhooks', $this->features, true)) {
            $this->line('Webhook-URL: '.$this->webhookUrl);
        }
    }

    public function stepSummaryConfirm(): void
    {
        $confirm = confirm(
            label: 'Passt diese Konfiguration?',
            default: true,
        );

        if (! $confirm) {
            $this->warn('Demo abgebrochen — keine Änderungen vorgenommen.');

            return;
        }

        $persist = confirm(
            label: 'Demo: Konfiguration nach storage/app/moox/prompts/project-setup.json schreiben?',
            default: false,
        );

        if (! $persist) {
            $this->info('⚪ Demo-Konfiguration nicht gespeichert (nur Vorschau).');

            return;
        }

        $config = [
            'project' => $this->projectName,
            'environment' => $this->environment,
            'features' => $this->features,
            'webhook_url' => $this->webhookUrl,
        ];

        Storage::disk('local')->put(
            'moox/prompts/project-setup.json',
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $this->info('✅ Demo-Konfiguration gespeichert unter storage/app/moox/prompts/project-setup.json');
    }

    public function stepOutro(): void
    {
        $this->info('=== Demo: Projekt Setup abgeschlossen ===');
    }
}
