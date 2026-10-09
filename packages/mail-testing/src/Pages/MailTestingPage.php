<?php

declare(strict_types=1);

namespace Moox\MailTesting\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Js;
use Moox\MailTemplate\Models\MailTemplate;
use Moox\MailTemplate\Support\MailSendConfig;
use Moox\MailTesting\Enums\Engine;
use Moox\MailTesting\Enums\FillMode;
use Moox\MailTesting\Enums\PersistBackend;
use Moox\MailTesting\Enums\RunStatus;
use Moox\MailTesting\Jobs\RenderMailTestingRunJob;
use Moox\MailTesting\Models\MailTestingMessage;
use Moox\MailTesting\Models\MailTestingRun;
use Moox\MailTesting\Support\HtmlLength;
use Moox\MailTesting\Support\MailTestingWorkerStatus;
use Moox\MailTesting\Support\NormalizedHtml;
use Moox\MailTesting\Support\VariableBinding;
use Moox\MailTesting\Support\VariableStore;
use Moox\Mjml\Enums\ValidationLevel;

/**
 * @property-read Schema $form
 */
class MailTestingPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $slug = 'mail-testing';

    protected static ?int $navigationSort = 80;

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess() && parent::shouldRegisterNavigation();
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()?->user();

        if ($user && method_exists($user, 'can')) {
            return (bool) $user->can('View:MailTestingPage');
        }

        return parent::canAccess();
    }

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * @var array<string, string>
     */
    public array $templateSlugOptions = [];

    /**
     * @var array<string, array<string, string>>
     */
    public array $templateLocales = [];

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
        $this->hydrateTemplateOptions();

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
            'source_template_slug' => null,
            'source_template_locale' => null,
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
                    ->columns(1)
                    ->schema([
                        Select::make('source_template_slug')
                            ->label(__('mail-testing::translations.source_template'))
                            ->placeholder(__('mail-testing::translations.source_template_placeholder'))
                            ->helperText(__('mail-testing::translations.source_template_help'))
                            ->options(function (): array {
                                if ($this->templateSlugOptions === []) {
                                    $this->hydrateTemplateOptions();
                                }

                                return $this->templateSlugOptions;
                            })
                            ->native()
                            ->required()
                            ->live()
                            ->extraInputAttributes([
                                'x-on:change' => $this->syncLocaleSelectOnTemplateChangeScript(),
                            ])
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                $slug = is_string($state) && $state !== '' ? $state : null;
                                $options = $this->localeOptionsForTemplate($slug);
                                $set('source_template_locale', array_key_first($options));
                            }),
                        Select::make('source_template_locale')
                            ->label(__('mail-testing::translations.source_template_locale'))
                            ->placeholder(__('mail-testing::translations.source_template_locale_placeholder'))
                            ->helperText(__('mail-testing::translations.source_template_locale_help'))
                            ->options(fn (Get $get): array => $this->localeOptionsForTemplate(
                                is_string($get('source_template_slug')) ? $get('source_template_slug') : null,
                            ))
                            ->native()
                            ->disabled(fn (Get $get): bool => ! filled($get('source_template_slug')))
                            ->required()
                            ->validationMessages([
                                'required' => __('mail-testing::translations.locale_required'),
                            ])
                            ->live(),
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
            Action::make('start')
                ->label(__('mail-testing::translations.start'))
                ->icon('heroicon-o-play')
                ->color(fn (): string => MailTestingWorkerStatus::shouldQueue() ? 'primary' : 'gray')
                ->disabled(fn (): bool => ! MailTestingWorkerStatus::shouldQueue())
                ->tooltip(fn (): ?string => MailTestingWorkerStatus::shouldQueue()
                    ? null
                    : __('mail-testing::translations.queue_required'))
                ->extraAttributes(fn (): array => MailTestingWorkerStatus::shouldQueue()
                    ? []
                    : ['class' => 'fi-color-gray'])
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

        if ($isOpen) {
            return '10s';
        }

        if (MailTestingWorkerStatus::usesQueue() && ! MailTestingWorkerStatus::isActive()) {
            return '10s';
        }

        return null;
    }

    public function workerBadgeColor(): string
    {
        return MailTestingWorkerStatus::shouldQueue() ? 'success' : 'gray';
    }

    public function workerBadgeIcon(): string
    {
        return MailTestingWorkerStatus::shouldQueue()
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

    public function workerStatusLabel(): string
    {
        return MailTestingWorkerStatus::shouldQueue()
            ? __('mail-testing::translations.worker_active')
            : __('mail-testing::translations.queue_required');
    }

    public function workerCommand(): string
    {
        return MailTestingWorkerStatus::workerCommand();
    }

    public function renderCommand(): string
    {
        return MailTestingWorkerStatus::renderCommand($this->data ?? []);
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
            'template_slug' => (string) ($state['source_template_slug'] ?? ''),
            'locale' => $this->selectedLocale($state),
            'variables' => array_map(
                fn (VariableBinding $binding): array => $binding->toArray(),
                VariableBinding::collect($state['variables'] ?? []),
            ),
        ];
    }

    public function start(): void
    {
        if (! MailTestingWorkerStatus::shouldQueue()) {
            Notification::make()
                ->warning()
                ->title(__('mail-testing::translations.queue_required'))
                ->send();

            return;
        }

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

        RenderMailTestingRunJob::dispatch($run->getKey());

        Notification::make()
            ->success()
            ->title(__('mail-testing::translations.run_queued', ['id' => $run->getKey()]))
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

    /**
     * @param  array<string, mixed>  $state
     */
    private function selectedLocale(array $state): ?string
    {
        $locale = trim((string) ($state['source_template_locale'] ?? ''));

        return $locale !== '' ? $locale : null;
    }

    /**
     * @return array<string, string>
     */
    private function localeOptionsForTemplate(?string $slug): array
    {
        if (! is_string($slug) || $slug === '') {
            return [];
        }

        if (! array_key_exists($slug, $this->templateLocales)) {
            $this->templateLocales[$slug] = $this->queryLocaleOptions($slug);
            $this->templateSlugOptions[$slug] = $slug;
            ksort($this->templateSlugOptions);
        }

        return $this->templateLocales[$slug];
    }

    private function hydrateTemplateOptions(): void
    {
        $labels = MailSendConfig::localeOptions();
        $slugs = [];
        $locales = [];

        $templates = MailTemplate::query()
            ->with('translations')
            ->orderBy('slug')
            ->get(['id', 'slug']);

        foreach ($templates as $template) {
            $slug = (string) $template->slug;
            $slugs[$slug] = $slug;
            $locales[$slug] = $this->optionsFromTranslations($template, $labels);
        }

        $this->templateSlugOptions = $slugs;
        $this->templateLocales = $locales;
    }

    /**
     * @return array<string, string>
     */
    private function queryLocaleOptions(string $slug): array
    {
        $template = MailTemplate::query()
            ->with('translations')
            ->where('slug', $slug)
            ->first();

        if (! $template instanceof MailTemplate) {
            return [];
        }

        return $this->optionsFromTranslations($template, MailSendConfig::localeOptions());
    }

    /**
     * @param  array<string, string>  $labels
     * @return array<string, string>
     */
    private function optionsFromTranslations(MailTemplate $template, array $labels): array
    {
        $options = [];

        foreach ($template->translations as $translation) {
            $locale = trim((string) $translation->getAttribute('locale'));

            if ($locale === '') {
                continue;
            }

            $options[$locale] = $labels[$locale] ?? $locale;
        }

        ksort($options);

        return $options;
    }

    private function syncLocaleSelectOnTemplateChangeScript(): string
    {
        $placeholder = Js::from(__('mail-testing::translations.source_template_locale_placeholder'));

        return <<<JS
            const localeSelect = document.getElementById('form.source_template_locale');
            if (! localeSelect) {
                return;
            }

            const slug = \$event.target.value;
            const locales = (\$wire.templateLocales && \$wire.templateLocales[slug]) ? \$wire.templateLocales[slug] : {};

            localeSelect.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = {$placeholder};
            localeSelect.appendChild(placeholder);

            Object.keys(locales).forEach((value) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = locales[value];
                localeSelect.appendChild(option);
            });

            const first = Object.keys(locales)[0] ?? '';
            localeSelect.value = first;
            localeSelect.disabled = first === '';
        JS;
    }
}
