@php
    $run = $this->latestRun();
    $engineRuns = $this->engineRuns();
    $comparison = $this->comparison();
    $workerActive = $this->workerIsActive();
    $willQueue = $this->willQueue();
    $workerCommand = $this->workerCommand();
    $renderCommand = $this->renderCommand();
    $hint = $willQueue
        ? __('mail-testing::translations.worker_hint_active')
        : ($workerActive
            ? __('mail-testing::translations.queue_sync')
            : __('mail-testing::translations.worker_hint_inactive', [
                'queue' => \Moox\MailTesting\Support\MailTestingWorkerStatus::queueName(),
            ]));
    $completedRuns = array_values(array_filter(
        $engineRuns,
        fn (\Moox\MailTesting\Models\MailTestingRun $engineRun): bool => $engineRun->status === \Moox\MailTesting\Enums\RunStatus::Completed,
    ));
    $bestTimes = [];
    if (count($completedRuns) >= 2) {
        foreach (['compose_ms', 'convert_ms', 'persist_ms', 'generation_ms', 'total_ms'] as $metric) {
            $bestTimes[$metric] = min(array_map(
                fn (\Moox\MailTesting\Models\MailTestingRun $engineRun): int => (int) $engineRun->{$metric},
                $completedRuns,
            ));
        }
    }
    $metricKeys = ['compose_ms', 'convert_ms', 'persist_ms', 'generation_ms', 'total_ms'];
@endphp

<div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 2rem;">
    <x-filament::section
        :heading="__('mail-testing::translations.commands')"
        :description="$hint"
        icon="heroicon-o-signal"
        :icon-color="$this->workerBadgeColor()"
        collapsible
        :collapsed="$willQueue"
    >
        <x-slot name="afterHeader">
            <span class="inline-flex items-center gap-2">
                @if ($workerActive && \Moox\MailTesting\Support\MailTestingWorkerStatus::usesQueue())
                    <span class="relative flex size-2.5" aria-hidden="true">
                        <span class="absolute inline-flex size-full rounded-full bg-success-400 opacity-75 motion-safe:animate-ping"></span>
                        <span class="relative inline-flex size-2.5 rounded-full bg-success-500"></span>
                    </span>
                @endif

                <x-filament::badge
                    :color="$this->workerBadgeColor()"
                    :icon="$this->workerBadgeIcon()"
                >
                    {{ $this->workerStatusLabel() }}
                </x-filament::badge>
            </span>
        </x-slot>

        <div class="grid gap-4">
            @include('mail-testing::pages.partials.command', [
                'label' => __('mail-testing::translations.worker_command'),
                'command' => $workerCommand,
            ])
            @include('mail-testing::pages.partials.command', [
                'label' => __('mail-testing::translations.render_command'),
                'command' => $renderCommand,
            ])
        </div>
    </x-filament::section>

    <x-filament::section
        :heading="__('mail-testing::translations.last_run')"
        :description="__('mail-testing::translations.last_run_help')"
        icon="heroicon-o-chart-bar"
        icon-color="gray"
    >
        @if ($engineRuns !== [])
            @if ($run)
                <x-slot name="afterHeader">
                    <x-filament::badge
                        :color="$run->status->color()"
                        :icon="$run->status->icon()"
                    >
                        {{ $run->engine->label() }}
                        · {{ $run->status->label() }}
                        · {{ $run->processed }}/{{ $run->count }}
                    </x-filament::badge>
                </x-slot>
            @endif

            @if ($run && in_array($run->status, [\Moox\MailTesting\Enums\RunStatus::Pending, \Moox\MailTesting\Enums\RunStatus::Running], true))
                <div
                    class="mb-6 h-2 overflow-hidden rounded-full bg-gray-950/5 dark:bg-white/10"
                    role="progressbar"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="{{ $this->runProgress($run) }}"
                    aria-label="{{ $run->status->label() }}"
                >
                    <div
                        class="h-full rounded-full bg-primary-600 transition-all duration-300 dark:bg-primary-400"
                        style="width: {{ $this->runProgress($run) }}%"
                    ></div>
                </div>
            @endif

            <div wire:key="mail-testing-runs-{{ collect($engineRuns)->map(fn ($engineRun) => $engineRun->id.'-'.$engineRun->status->value.'-'.$engineRun->processed.'-'.$engineRun->total_ms)->implode('|') }}">
                <table class="fi-ta-table mail-testing-runs" style="width: 100%; border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr>
                            <th class="fi-ta-header-cell" scope="col" style="text-align: center; white-space: normal; padding: 0.85rem 1.25rem;">{{ __('mail-testing::translations.engine') }}</th>
                            <th class="fi-ta-header-cell" scope="col" style="text-align: center; white-space: normal; padding: 0.85rem 1.25rem;">{{ __('mail-testing::translations.status') }}</th>
                            <th class="fi-ta-header-cell" scope="col" style="text-align: center; white-space: normal; padding: 0.85rem 1.25rem;">{{ __('mail-testing::translations.count') }}</th>
                            <th class="fi-ta-header-cell" scope="col" title="{{ __('mail-testing::translations.compose_ms_help') }}" style="text-align: center; white-space: normal; padding: 0.85rem 1.25rem;">{{ __('mail-testing::translations.compose_ms') }}</th>
                            <th class="fi-ta-header-cell" scope="col" title="{{ __('mail-testing::translations.convert_ms_help') }}" style="text-align: center; white-space: normal; padding: 0.85rem 1.25rem;">{{ __('mail-testing::translations.convert_ms') }}</th>
                            <th class="fi-ta-header-cell" scope="col" title="{{ __('mail-testing::translations.persist_ms_help') }}" style="text-align: center; white-space: normal; padding: 0.85rem 1.25rem;">{{ __('mail-testing::translations.persist_ms') }}</th>
                            <th class="fi-ta-header-cell" scope="col" title="{{ __('mail-testing::translations.generation_ms_help') }}" style="text-align: center; white-space: normal; padding: 0.85rem 1.25rem;">{{ __('mail-testing::translations.generation_ms') }}</th>
                            <th class="fi-ta-header-cell" scope="col" title="{{ __('mail-testing::translations.total_ms_help') }}" style="text-align: center; white-space: normal; padding: 0.85rem 1.25rem;">{{ __('mail-testing::translations.total_ms') }}</th>
                            <th class="fi-ta-header-cell" scope="col" style="text-align: center; white-space: nowrap; padding: 0.85rem 1.25rem;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($engineRuns as $engineRun)
                            <tr class="fi-ta-row" wire:key="mail-testing-run-{{ $engineRun->id }}-{{ $engineRun->status->value }}-{{ $engineRun->processed }}">
                                <td class="fi-ta-cell" style="text-align: center; vertical-align: middle; white-space: nowrap; padding: 1rem 1.25rem;">
                                    <span>{{ $engineRun->engine->label() }}</span>
                                </td>
                                <td class="fi-ta-cell" style="text-align: center; vertical-align: middle; padding: 1rem 1.25rem;">
                                    <span style="display: inline-flex; justify-content: center;">
                                        <x-filament::badge
                                            :color="$engineRun->status->color()"
                                            :icon="$engineRun->status->icon()"
                                        >
                                            {{ $engineRun->status->label() }}
                                        </x-filament::badge>
                                    </span>
                                </td>
                                <td class="fi-ta-cell tabular-nums" style="text-align: center; vertical-align: middle; white-space: nowrap; padding: 1rem 1.25rem;">{{ $engineRun->processed }}/{{ $engineRun->count }}</td>
                                @foreach ($metricKeys as $metric)
                                    @php
                                        $isCompleted = $engineRun->status === \Moox\MailTesting\Enums\RunStatus::Completed;
                                        $isBest = $isCompleted && isset($bestTimes[$metric]) && $engineRun->{$metric} === $bestTimes[$metric];
                                    @endphp
                                    <td
                                        class="fi-ta-cell tabular-nums"
                                        title="{{ $isCompleted ? $engineRun->{$metric}.' ms' : '' }}"
                                        style="text-align: center; vertical-align: middle; white-space: nowrap; padding: 1rem 1.25rem;{{ $isBest ? ' color: rgb(22 163 74);' : '' }}"
                                    >
                                        {{ $isCompleted ? \Moox\MailTesting\Support\DurationFormat::milliseconds((int) $engineRun->{$metric}) : '—' }}
                                    </td>
                                @endforeach
                                <td class="fi-ta-cell" style="text-align: center; vertical-align: middle; white-space: nowrap; padding: 1rem 1.25rem;">
                                    <x-filament::icon-button
                                        color="danger"
                                        icon="heroicon-o-trash"
                                        size="sm"
                                        :label="__('mail-testing::translations.delete_run')"
                                        :tooltip="__('mail-testing::translations.delete_run')"
                                        wire:click="deleteRun({{ $engineRun->getKey() }})"
                                        wire:confirm="{{ __('mail-testing::translations.delete_run_heading', ['engine' => $engineRun->engine->label()]) }} {{ __('mail-testing::translations.delete_run_description') }}"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                @foreach ($engineRuns as $engineRun)
                    @php
                        $htmlPath = $engineRun->htmlDirectoryPath();
                    @endphp
                    @if (is_string($htmlPath))
                        @include('mail-testing::pages.partials.command', [
                            'label' => __('mail-testing::translations.html_path', [
                                'engine' => $engineRun->engine->label(),
                            ]),
                            'command' => $htmlPath,
                        ])
                    @elseif ($engineRun->persist_backend === \Moox\MailTesting\Enums\PersistBackend::Database)
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('mail-testing::translations.html_in_database', [
                                'engine' => $engineRun->engine->label(),
                            ]) }}
                        </p>
                    @endif
                @endforeach
            </div>

            @foreach ($engineRuns as $engineRun)
                @if ($engineRun->error)
                    <div class="mt-4">
                        <x-filament::badge color="danger" icon="heroicon-m-exclamation-triangle">
                            {{ $engineRun->engine->label() }}
                            · {{ __('mail-testing::translations.error') }}
                        </x-filament::badge>
                        <p class="mt-2 text-sm text-danger-600 dark:text-danger-400">{{ $engineRun->error }}</p>
                    </div>
                @endif
            @endforeach
        @else
            <x-filament::empty-state
                :heading="__('mail-testing::translations.no_run')"
                :description="__('mail-testing::translations.no_run_help')"
                icon="heroicon-o-beaker"
                icon-color="gray"
                :contained="false"
                compact
            />
        @endif
    </x-filament::section>

    @if (is_array($comparison))
        <x-filament::section
            :heading="__('mail-testing::translations.comparison')"
            icon="heroicon-o-squares-2x2"
            icon-color="gray"
        >
            @unless ($comparison['matching'])
                <x-filament::badge color="warning" icon="heroicon-m-exclamation-triangle" class="mb-4">
                    {{ __('mail-testing::translations.comparison_mismatch') }}
                </x-filament::badge>
            @endunless

            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                {{ __('mail-testing::translations.identical_count', [
                    'identical' => $comparison['identical'],
                    'compared' => $comparison['compared'],
                ]) }}
            </p>

            @php
                $phpLength = $comparison['php_length'] ?? ['bytes' => 0, 'characters' => 0];
                $nodeLength = $comparison['node_length'] ?? ['bytes' => 0, 'characters' => 0];
                $metricsDiffer = $phpLength['bytes'] !== $nodeLength['bytes']
                    || $phpLength['characters'] !== $nodeLength['characters'];
            @endphp

            <div class="grid gap-4 lg:grid-cols-2">
                @include('mail-testing::pages.partials.command', [
                    'label' => 'PHP ('.\Moox\MailTesting\Support\DurationFormat::milliseconds((int) $comparison['php']->total_ms).')',
                    'command' => $comparison['sample_php'] ?? '',
                    'wrap' => true,
                    'meta' => \Moox\MailTesting\Support\HtmlLength::formatBytes((int) $phpLength['bytes'])
                        .' · '
                        .\Moox\MailTesting\Support\HtmlLength::formatCharacters((int) $phpLength['characters']),
                    'metaWarn' => $metricsDiffer,
                ])
                @include('mail-testing::pages.partials.command', [
                    'label' => 'Node ('.\Moox\MailTesting\Support\DurationFormat::milliseconds((int) $comparison['node']->total_ms).')',
                    'command' => $comparison['sample_node'] ?? '',
                    'wrap' => true,
                    'meta' => \Moox\MailTesting\Support\HtmlLength::formatBytes((int) $nodeLength['bytes'])
                        .' · '
                        .\Moox\MailTesting\Support\HtmlLength::formatCharacters((int) $nodeLength['characters']),
                    'metaWarn' => $metricsDiffer,
                ])
            </div>

            @if ($comparison['same_delta'])
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('mail-testing::translations.same_delta') }}
                </p>
            @endif
        </x-filament::section>
    @endif
</div>
