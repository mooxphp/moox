@php
    use Moox\MailTesting\Enums\PersistBackend;
    use Moox\MailTesting\Enums\RunStatus;
    use Moox\MailTesting\Models\MailTestingRun;
    use Moox\MailTesting\Support\DurationFormat;
    use Moox\MailTesting\Support\HtmlLength;
    use Moox\MailTesting\Support\MailTestingWorkerStatus;

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
                'queue' => MailTestingWorkerStatus::queueName(),
            ]));
    $completedRuns = array_values(array_filter(
        $engineRuns,
        fn (MailTestingRun $engineRun): bool => $engineRun->status === RunStatus::Completed,
    ));
    $bestTimes = [];
    if (count($completedRuns) >= 2) {
        foreach (['compose_ms', 'convert_ms', 'persist_ms', 'generation_ms', 'total_ms'] as $metric) {
            $bestTimes[$metric] = min(array_map(
                fn (MailTestingRun $engineRun): int => (int) $engineRun->{$metric},
                $completedRuns,
            ));
        }
    }
    $metricKeys = ['compose_ms', 'convert_ms', 'persist_ms', 'generation_ms', 'total_ms'];
    $columns = [
        'engine' => __('mail-testing::translations.engine'),
        'status' => __('mail-testing::translations.status'),
        'count' => __('mail-testing::translations.count'),
        'compose_ms' => __('mail-testing::translations.compose_ms'),
        'convert_ms' => __('mail-testing::translations.convert_ms'),
        'persist_ms' => __('mail-testing::translations.persist_ms'),
        'generation_ms' => __('mail-testing::translations.generation_ms'),
        'total_ms' => __('mail-testing::translations.total_ms'),
    ];
@endphp

<div class="mt-6 grid gap-6">
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
                @if ($workerActive && MailTestingWorkerStatus::usesQueue())
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

            @if ($run && in_array($run->status, [RunStatus::Pending, RunStatus::Running], true))
                <div class="mb-6 grid gap-2">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="font-medium text-gray-950 dark:text-white">{{ $run->status->label() }}</span>
                        <span class="tabular-nums text-gray-500 dark:text-gray-400">{{ $run->processed }}/{{ $run->count }}</span>
                    </div>
                    <div
                        class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10"
                        role="progressbar"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-valuenow="{{ $this->runProgress($run) }}"
                        aria-label="{{ $run->status->label() }}"
                    >
                        <div
                            class="h-full rounded-full bg-primary-600 dark:bg-primary-400"
                            style="width: {{ $this->runProgress($run) }}%"
                        ></div>
                    </div>
                </div>
            @endif

            @if ($bestTimes !== [])
                <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('mail-testing::translations.fastest_hint') }}
                </p>
            @endif

            <div
                class="overflow-x-auto"
                wire:key="mail-testing-runs-{{ collect($engineRuns)->map(fn ($engineRun) => $engineRun->id.'-'.$engineRun->status->value.'-'.$engineRun->processed.'-'.$engineRun->total_ms)->implode('|') }}"
            >
                <table class="fi-ta-table fi-ta-table-stacked-on-mobile">
                    <thead>
                        <tr>
                            @foreach ($columns as $column => $heading)
                                <th
                                    class="fi-ta-header-cell fi-wrapped {{ in_array($column, ['engine', 'status'], true) ? '' : 'fi-align-end' }}"
                                    scope="col"
                                    @if (in_array($column, $metricKeys, true))
                                        title="{{ __('mail-testing::translations.'.$column.'_help') }}"
                                    @endif
                                >
                                    {{ $heading }}
                                </th>
                            @endforeach
                            <th class="fi-ta-header-cell fi-align-end" scope="col">
                                <span class="sr-only">{{ __('mail-testing::translations.delete_run') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($engineRuns as $engineRun)
                            <tr class="fi-ta-row" wire:key="mail-testing-run-{{ $engineRun->id }}-{{ $engineRun->status->value }}-{{ $engineRun->processed }}">
                                <td class="fi-ta-cell">
                                    <div class="fi-ta-cell-label">{{ $columns['engine'] }}</div>
                                    <div class="fi-ta-cell-content font-medium">{{ $engineRun->engine->label() }}</div>
                                </td>
                                <td class="fi-ta-cell">
                                    <div class="fi-ta-cell-label">{{ $columns['status'] }}</div>
                                    <div class="fi-ta-cell-content">
                                        <x-filament::badge
                                            :color="$engineRun->status->color()"
                                            :icon="$engineRun->status->icon()"
                                        >
                                            {{ $engineRun->status->label() }}
                                        </x-filament::badge>
                                    </div>
                                </td>
                                <td class="fi-ta-cell fi-align-end">
                                    <div class="fi-ta-cell-label">{{ $columns['count'] }}</div>
                                    <div class="fi-ta-cell-content tabular-nums">{{ $engineRun->processed }}/{{ $engineRun->count }}</div>
                                </td>
                                @foreach ($metricKeys as $metric)
                                    @php
                                        $isCompleted = $engineRun->status === RunStatus::Completed;
                                        $isBest = $isCompleted && isset($bestTimes[$metric]) && (int) $engineRun->{$metric} === (int) $bestTimes[$metric];
                                    @endphp
                                    <td class="fi-ta-cell fi-align-end" @if ($isCompleted) title="{{ $engineRun->{$metric} }} ms" @endif>
                                        <div class="fi-ta-cell-label">{{ $columns[$metric] }}</div>
                                        <div @class([
                                            'fi-ta-cell-content inline-flex items-center justify-end gap-1 tabular-nums',
                                            'font-medium text-success-600 dark:text-success-400' => $isBest,
                                        ])>
                                            @if ($isBest)
                                                <x-filament::icon icon="heroicon-m-check" class="fi-icon fi-size-sm" />
                                            @endif
                                            {{ $isCompleted ? DurationFormat::milliseconds((int) $engineRun->{$metric}) : '—' }}
                                        </div>
                                    </td>
                                @endforeach
                                <td class="fi-ta-cell fi-align-end">
                                    <div class="fi-ta-actions justify-end">
                                        <x-filament::icon-button
                                            color="danger"
                                            icon="heroicon-o-trash"
                                            size="sm"
                                            :label="__('mail-testing::translations.delete_run')"
                                            :tooltip="__('mail-testing::translations.delete_run')"
                                            wire:click="deleteRun({{ $engineRun->getKey() }})"
                                            wire:confirm="{{ __('mail-testing::translations.delete_run_heading', ['engine' => $engineRun->engine->label()]) }} {{ __('mail-testing::translations.delete_run_description') }}"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 grid gap-4">
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
                    @elseif ($engineRun->persist_backend === PersistBackend::Database)
                        <x-filament::callout
                            color="gray"
                            icon="heroicon-o-circle-stack"
                            :description="__('mail-testing::translations.html_in_database', [
                                'engine' => $engineRun->engine->label(),
                            ])"
                        />
                    @endif
                @endforeach
            </div>

            @foreach ($engineRuns as $engineRun)
                @if ($engineRun->error)
                    <div class="mt-4">
                        <x-filament::callout
                            color="danger"
                            icon="heroicon-o-exclamation-triangle"
                            :heading="$engineRun->engine->label().' · '.__('mail-testing::translations.error')"
                            :description="$engineRun->error"
                        />
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
            <div class="grid gap-4">
                @unless ($comparison['matching'])
                    <x-filament::callout
                        color="warning"
                        icon="heroicon-o-exclamation-triangle"
                        :description="__('mail-testing::translations.comparison_mismatch')"
                    />
                @endunless

                <p class="text-sm text-gray-500 dark:text-gray-400">
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
                        'label' => 'PHP ('.DurationFormat::milliseconds((int) $comparison['php']->total_ms).')',
                        'command' => $comparison['sample_php'] ?? '',
                        'wrap' => true,
                        'meta' => HtmlLength::formatBytes((int) $phpLength['bytes'])
                            .' · '
                            .HtmlLength::formatCharacters((int) $phpLength['characters']),
                        'metaWarn' => $metricsDiffer,
                    ])
                    @include('mail-testing::pages.partials.command', [
                        'label' => 'Node ('.DurationFormat::milliseconds((int) $comparison['node']->total_ms).')',
                        'command' => $comparison['sample_node'] ?? '',
                        'wrap' => true,
                        'meta' => HtmlLength::formatBytes((int) $nodeLength['bytes'])
                            .' · '
                            .HtmlLength::formatCharacters((int) $nodeLength['characters']),
                        'metaWarn' => $metricsDiffer,
                    ])
                </div>

                @if ($comparison['same_delta'])
                    <x-filament::callout
                        color="gray"
                        icon="heroicon-o-information-circle"
                        :description="__('mail-testing::translations.same_delta')"
                    />
                @endif
            </div>
        </x-filament::section>
    @endif
</div>
