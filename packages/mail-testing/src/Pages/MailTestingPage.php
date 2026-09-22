<?php

declare(strict_types=1);

namespace Moox\MailTesting\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Moox\MailTemplate\Models\MailLayout;
use Moox\MailTesting\Enums\Engine;
use Moox\MailTesting\Enums\FillMode;
use Moox\MailTesting\Enums\PersistBackend;
use Moox\MailTesting\Enums\RunStatus;
use Moox\MailTesting\Jobs\RenderMailTestingRunJob;
use Moox\MailTesting\Models\MailTestingMessage;
use Moox\MailTesting\Models\MailTestingRun;
use Moox\MailTesting\Support\EnsureTestTemplate;
use Moox\MailTesting\Support\HtmlLength;
use Moox\MailTesting\Support\MailTestingRunService;
use Moox\MailTesting\Support\MailTestingWorkerStatus;
use Moox\MailTesting\Support\NormalizedHtml;
use Moox\MailTesting\Support\VariableBinding;
use Moox\MailTesting\Support\VariableStore;
use Moox\Mjml\Enums\ValidationLevel;
use Throwable;

/**
 * @property-read Schema $form
 */
class MailTestingPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $slug = 'mail-testing';

    protected static ?int $navigationSort = 80;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('mail-testing::translations.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('mail-testing::translations.navigation_label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('mail-testing::translations.page_title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('mail-testing::translations.page_description');
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function mount(): void
    {
        $saved = app(VariableStore::class)->read();

        $this->form->fill([
            'count' => (int) config('mail-testing.default_count', 500),
            'engine' => Engine::Php->value,
            'persist_backend' => PersistBackend::Storage->value,
            'validation_level' => ValidationLevel::Soft->value,
            'minify' => false,
            'beautify' => false,
            'keep_comments' => false,
            'ignore_includes' => false,
            'recipient_mode' => $saved['recipient_mode'],
            'variables' => $saved['variables'],
            'source_layout_id' => null,
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('mail-testing::translations.fieldset_test'))
                    ->description(__('mail-testing::translations.fieldset_test_help'))
                    ->icon('heroicon-o-play')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        Select::make('source_layout_id')
                            ->label(__('mail-testing::translations.source_layout'))
                            ->placeholder(__('mail-testing::translations.source_layout_placeholder'))
                            ->helperText(__('mail-testing::translations.source_layout_help'))
                            ->options(fn (): array => MailLayout::query()
                                ->orderBy('slug')
                                ->pluck('slug', 'id')
                                ->all())
                            ->searchable()
                            ->columnSpanFull(),
                        TextInput::make('count')
                            ->label(__('mail-testing::translations.count'))
                            ->numeric()
                            ->required()
                            ->minValue((int) config('mail-testing.min_count', 1))
                            ->integer()
                            ->live(onBlur: true),
                        ToggleButtons::make('engine')
                            ->label(__('mail-testing::translations.engine'))
                            ->options([
                                Engine::Php->value => 'PHP',
                                Engine::Node->value => 'Node',
                            ])
                            ->icons([
                                Engine::Php->value => 'heroicon-m-code-bracket',
                                Engine::Node->value => 'heroicon-m-cube',
                            ])
                            ->grouped()
                            ->required()
                            ->live(),
                        ToggleButtons::make('persist_backend')
                            ->label(__('mail-testing::translations.persist'))
                            ->options([
                                PersistBackend::Storage->value => __('mail-testing::translations.persist_storage'),
                                PersistBackend::Database->value => __('mail-testing::translations.persist_database'),
                            ])
                            ->grouped()
                            ->required()
                            ->live()
                            ->helperText(function (): string {
                                $backend = PersistBackend::tryFrom((string) ($this->data['persist_backend'] ?? PersistBackend::Storage->value));

                                if ($backend === PersistBackend::Database) {
                                    return __('mail-testing::translations.persist_database_help');
                                }

                                return __('mail-testing::translations.persist_storage_help');
                            }),
                    ]),
                Section::make(__('mail-testing::translations.fieldset_variables'))
                    ->description(__('mail-testing::translations.fieldset_variables_help'))
                    ->icon('heroicon-o-queue-list')
                    ->collapsed()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        ToggleButtons::make('recipient_mode')
                            ->label(__('mail-testing::translations.recipient'))
                            ->options(FillMode::options())
                            ->grouped()
                            ->required()
                            ->live()
                            ->helperText(__('mail-testing::translations.recipient_help')),
                        Repeater::make('variables')
                            ->label(__('mail-testing::translations.variables'))
                            ->addActionLabel(__('mail-testing::translations.variables_add'))
                            ->helperText(__('mail-testing::translations.variables_help'))
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->columnSpanFull()
                            ->columns(['default' => 1, 'md' => 3])
                            ->schema([
                                TextInput::make('token')
                                    ->label(__('mail-testing::translations.variable_token'))
                                    ->placeholder('invoiceNumber')
                                    ->required(),
                                ToggleButtons::make('mode')
                                    ->label(__('mail-testing::translations.variable_mode'))
                                    ->options(FillMode::options())
                                    ->grouped()
                                    ->default(FillMode::Demo->value)
                                    ->required(),
                                TextInput::make('value')
                                    ->label(__('mail-testing::translations.variable_value'))
                                    ->placeholder('RE-2026-001')
                                    ->helperText(__('mail-testing::translations.variable_value_help')),
                            ]),
                        Actions::make([
                            Action::make('saveVariables')
                                ->label(__('mail-testing::translations.save_variables'))
                                ->icon('heroicon-o-check')
                                ->action('saveVariables'),
                        ])
                            ->alignEnd()
                            ->columnSpanFull(),
                    ]),
                Section::make(__('mail-testing::translations.fieldset_mjml'))
                    ->description(__('mail-testing::translations.fieldset_mjml_help'))
                    ->icon('heroicon-o-code-bracket')
                    ->collapsed()
                    ->schema([
                        ToggleButtons::make('validation_level')
                            ->label(__('mail-testing::translations.validation'))
                            ->options([
                                ValidationLevel::Skip->value => 'skip',
                                ValidationLevel::Soft->value => 'soft',
                                ValidationLevel::Strict->value => 'strict',
                            ])
                            ->grouped()
                            ->required()
                            ->live(),
                        Fieldset::make(__('mail-testing::translations.mjml_flags'))
                            ->columns(['default' => 1, 'sm' => 2, 'xl' => 4])
                            ->schema([
                                Toggle::make('minify')
                                    ->label(__('mail-testing::translations.minify'))
                                    ->live(),
                                Toggle::make('beautify')
                                    ->label(__('mail-testing::translations.beautify'))
                                    ->live(),
                                Toggle::make('keep_comments')
                                    ->label(__('mail-testing::translations.keep_comments'))
                                    ->live(),
                                Toggle::make('ignore_includes')
                                    ->label(__('mail-testing::translations.ignore_includes'))
                                    ->live(),
                            ]),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('start'),
                View::make('mail-testing::pages.results')
                    ->poll(fn (): ?string => $this->getPollingInterval()),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('ensureTemplate')
                ->label(__('mail-testing::translations.ensure_template'))
                ->icon('heroicon-o-document-plus')
                ->color('gray')
                ->modalHeading(__('mail-testing::translations.ensure_template_heading'))
                ->modalDescription(__('mail-testing::translations.ensure_template_description'))
                ->modalSubmitActionLabel(__('mail-testing::translations.ensure_template_submit'))
                ->modalWidth(Width::FiveExtraLarge)
                ->fillForm(fn (): array => [
                    'mail_content' => EnsureTestTemplate::currentOrDefaultContent(),
                ])
                ->schema([
                    Textarea::make('mail_content')
                        ->label(__('mail-testing::translations.mail_content'))
                        ->helperText(__('mail-testing::translations.mail_content_help'))
                        ->rows(18)
                        ->required()
                        ->extraInputAttributes([
                            'class' => 'font-mono text-sm',
                            'spellcheck' => 'false',
                        ]),
                ])
                ->action(function (array $data): void {
                    $this->saveTestTemplate((string) ($data['mail_content'] ?? ''));
                }),
            Action::make('start')
                ->label(fn (): string => MailTestingWorkerStatus::shouldQueue()
                    ? __('mail-testing::translations.start_queued')
                    : __('mail-testing::translations.start_inline'))
                ->icon('heroicon-o-play')
                ->color(fn (): string => MailTestingWorkerStatus::shouldQueue() ? 'primary' : 'warning')
                ->submit('start')
                ->formId('form'),
        ];
    }

    public function deleteRun(int $runId): void
    {
        $run = MailTestingRun::query()->find($runId);

        if (! $run instanceof MailTestingRun) {
            return;
        }

        $engine = $run->engine->label();
        $run->purge();

        Notification::make()
            ->title(__('mail-testing::translations.deleted_run', ['engine' => $engine]))
            ->success()
            ->send();
    }

    public function getPollingInterval(): ?string
    {
        $isOpen = MailTestingRun::query()
            ->whereIn('status', [RunStatus::Pending, RunStatus::Running])
            ->exists();

        return $isOpen ? '10s' : null;
    }

    public function workerBadgeColor(): string
    {
        if (! MailTestingWorkerStatus::usesQueue()) {
            return 'info';
        }

        return MailTestingWorkerStatus::isActive() ? 'success' : 'warning';
    }

    public function workerBadgeIcon(): string
    {
        if (! MailTestingWorkerStatus::usesQueue()) {
            return 'heroicon-m-bolt';
        }

        return MailTestingWorkerStatus::isActive()
            ? 'heroicon-m-signal'
            : 'heroicon-m-signal-slash';
    }

    public function runProgress(MailTestingRun $run): int
    {
        if ($run->count < 1) {
            return 0;
        }

        return (int) min(100, round(($run->processed / $run->count) * 100));
    }

    public function willQueue(): bool
    {
        return MailTestingWorkerStatus::shouldQueue();
    }

    public function workerIsActive(): bool
    {
        return MailTestingWorkerStatus::isActive();
    }

    public function workerStatusLabel(): string
    {
        if (! MailTestingWorkerStatus::usesQueue()) {
            return __('mail-testing::translations.queue_sync');
        }

        return MailTestingWorkerStatus::isActive()
            ? __('mail-testing::translations.worker_active', ['queue' => MailTestingWorkerStatus::queueName()])
            : __('mail-testing::translations.worker_inactive');
    }

    public function workerCommand(): string
    {
        return MailTestingWorkerStatus::workerCommand();
    }

    public function renderCommand(): string
    {
        return MailTestingWorkerStatus::renderCommand($this->data ?? []);
    }

    public function saveTestTemplate(string $mailContent): void
    {
        $selectedLayout = $this->form->getState()['source_layout_id'] ?? null;

        try {
            app(EnsureTestTemplate::class)->ensure(
                filled($selectedLayout) ? (int) $selectedLayout : null,
                $mailContent,
            );
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(__('mail-testing::translations.template_ready'))
            ->send();
    }

    public function saveVariables(): void
    {
        $state = $this->form->getState();
        $saved = app(VariableStore::class)->payloadFromState($state);
        app(VariableStore::class)->write($state);

        $this->data['recipient_mode'] = $saved['recipient_mode'];
        $this->data['variables'] = $saved['variables'];

        Notification::make()
            ->success()
            ->title(__('mail-testing::translations.variables_saved'))
            ->send();
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function runOptions(array $state): array
    {
        $recipient = FillMode::tryFrom((string) ($state['recipient_mode'] ?? '')) ?? FillMode::Random;

        return [
            'validation_level' => (string) $state['validation_level'],
            'minify' => (bool) $state['minify'],
            'beautify' => (bool) $state['beautify'],
            'keep_comments' => (bool) $state['keep_comments'],
            'ignore_includes' => (bool) $state['ignore_includes'],
            'recipient_mode' => $recipient->value,
            'layout_id' => filled($state['source_layout_id'] ?? null) ? (int) $state['source_layout_id'] : null,
            'variables' => array_map(
                fn (VariableBinding $binding): array => $binding->toArray(),
                VariableBinding::collect($state['variables'] ?? []),
            ),
        ];
    }

    public function start(): void
    {
        $state = $this->form->getState();
        $count = (int) $state['count'];
        $engine = Engine::from((string) $state['engine']);
        $persist = PersistBackend::from((string) $state['persist_backend']);
        $options = $this->runOptions($state);
        app(VariableStore::class)->write($state);

        $run = MailTestingRun::query()->create([
            'status' => RunStatus::Pending,
            'engine' => $engine,
            'persist_backend' => $persist,
            'count' => $count,
            'processed' => 0,
            'options' => $options,
            'options_fingerprint' => MailTestingRun::fingerprint($count, $persist, $options),
        ]);

        if (MailTestingWorkerStatus::shouldQueue()) {
            RenderMailTestingRunJob::dispatch($run->getKey());

            Notification::make()
                ->success()
                ->title(__('mail-testing::translations.run_queued', ['id' => $run->getKey()]))
                ->send();

            return;
        }

        set_time_limit(0);

        try {
            app(MailTestingRunService::class)->execute($run);
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(__('mail-testing::translations.run_done', ['id' => $run->getKey()]))
            ->send();
    }

    public function latestRun(): ?MailTestingRun
    {
        return MailTestingRun::query()->latest('id')->first();
    }

    /**
     * @return list<MailTestingRun>
     */
    public function engineRuns(): array
    {
        $runs = [];

        foreach ([Engine::Php, Engine::Node] as $engine) {
            $run = MailTestingRun::query()
                ->where('engine', $engine)
                ->latest('id')
                ->first();

            if ($run instanceof MailTestingRun) {
                $runs[] = $run;
            }
        }

        return $runs;
    }

    /**
     * @return array{
     *     php: MailTestingRun,
     *     node: MailTestingRun,
     *     matching: bool,
     *     identical: int,
     *     compared: int,
     *     sample_php: ?string,
     *     sample_node: ?string,
     *     php_length: array{bytes: int, characters: int},
     *     node_length: array{bytes: int, characters: int},
     *     same_delta: bool
     * }|null
     */
    public function comparison(): ?array
    {
        $php = MailTestingRun::query()
            ->where('engine', Engine::Php)
            ->where('status', RunStatus::Completed)
            ->latest('id')
            ->first();
        $node = MailTestingRun::query()
            ->where('engine', Engine::Node)
            ->where('status', RunStatus::Completed)
            ->latest('id')
            ->first();

        if (! $php instanceof MailTestingRun || ! $node instanceof MailTestingRun) {
            return null;
        }

        $matching = $php->options_fingerprint === $node->options_fingerprint
            && $php->count === $node->count;

        $phpHashes = MailTestingMessage::query()
            ->where('mail_testing_run_id', $php->getKey())
            ->orderBy('position')
            ->pluck('html_hash', 'position');
        $nodeHashes = MailTestingMessage::query()
            ->where('mail_testing_run_id', $node->getKey())
            ->orderBy('position')
            ->pluck('html_hash', 'position');

        $compared = 0;
        $identical = 0;
        $firstMismatch = null;

        foreach ($phpHashes as $position => $hash) {
            if (! $nodeHashes->has($position)) {
                continue;
            }

            $compared++;
            if ($hash === $nodeHashes->get($position)) {
                $identical++;
            } elseif ($firstMismatch === null) {
                $firstMismatch = (int) $position;
            }
        }

        $samplePhp = null;
        $sampleNode = null;
        $phpHtml = null;
        $nodeHtml = null;
        $position = $firstMismatch ?? 1;
        $phpMessage = MailTestingMessage::query()
            ->where('mail_testing_run_id', $php->getKey())
            ->where('position', $position)
            ->first();
        $nodeMessage = MailTestingMessage::query()
            ->where('mail_testing_run_id', $node->getKey())
            ->where('position', $position)
            ->first();

        if ($phpMessage instanceof MailTestingMessage) {
            $phpHtml = $phpMessage->resolvedHtml();
            $samplePhp = NormalizedHtml::forDisplay((string) $phpHtml);
        }

        if ($nodeMessage instanceof MailTestingMessage) {
            $nodeHtml = $nodeMessage->resolvedHtml();
            $sampleNode = NormalizedHtml::forDisplay((string) $nodeHtml);
        }

        $uniquePhp = $phpHashes->unique()->count();
        $sameDelta = $compared > 0 && $identical === 0 && $uniquePhp === 1 && $nodeHashes->unique()->count() === 1;

        return [
            'php' => $php,
            'node' => $node,
            'matching' => $matching,
            'identical' => $identical,
            'compared' => $compared,
            'sample_php' => $samplePhp,
            'sample_node' => $sampleNode,
            'php_length' => HtmlLength::of(is_string($phpHtml) ? $phpHtml : null),
            'node_length' => HtmlLength::of(is_string($nodeHtml) ? $nodeHtml : null),
            'same_delta' => $sameDelta,
        ];
    }
}
