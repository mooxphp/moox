<x-filament::widget>
    <x-filament::card wire:poll.5s="refreshData">
        <div class="grid grid-cols-1 gap-4">
            <x-filament::section class="min-w-0">
                <x-slot name="heading">Connection & Queue</x-slot>

                <form wire:submit.prevent="refreshData" class="mb-3 flex flex-wrap items-center gap-2">
                    <span class="text-sm font-semibold text-gray-700">Connection:</span>
                    <select id="connection-select" wire:model="connectionId"
                        class="fi-input block rounded-lg border-gray-300 px-2 py-1 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        @foreach ($availableConnections as $c)
                            <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                </form>

                <div class="mb-3 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                    <span
                        class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2 py-0.5">
                        <span class="h-2 w-2 rounded-full bg-primary-500"></span>
                        <span class="font-semibold">Queue</span>
                        <span class="text-gray-500">{{ number_format($queuedJobsCount, 0, ',', '.') }} Jobs</span>
                    </span>
                    <span
                        class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2 py-0.5">
                        <span class="text-[10px] uppercase tracking-wide text-gray-400">Driver</span>
                        <span class="font-medium text-gray-700">{{ $queueDriver }}</span>
                    </span>
                    @if ($queuedJobsByTypeSampled)
                        <span
                            class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-amber-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                            Stichprobe {{ $queuedJobsByTypeSampleSize }} / {{ $queuedJobsCount }}
                        </span>
                    @endif
                </div>

                <div class="mb-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                    @if (!empty($queuedJobsByType))
                        <div class="flex flex-wrap gap-2">
                            @foreach ($queuedJobsByType as $queuedJob)
                                <span
                                    class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2.5 py-0.5 text-xs text-gray-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-300"></span>
                                    <span class="font-medium">{{ $queuedJob['name'] }}</span>
                                    <span class="text-gray-500">· {{ $queuedJob['count'] }}</span>
                                </span>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-1 text-xs text-gray-500">
                            Keine wartenden Jobs gefunden (oder Queue-Backend aktuell nicht auslesbar).
                        </div>
                    @endif
                </div>

                <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-2">

                    @if (empty($levels))
                        <div class="text-xs text-gray-500 italic">Keine Endpunkte gefunden…</div>
                    @else
                        @php
                            $rowHeight = 92;
                            $paddingX = 24;
                            $paddingY = 24;
                            $nodeWidths = [];
                            $levelMaxWidth = [];
                            foreach ($levels as $levelIndex => $levelNodesForWidth) {
                                $levelMaxWidth[$levelIndex] = 176;
                                foreach ($levelNodesForWidth as $nodeForWidth) {
                                    $maxCharsForNode = max(
                                        strlen((string) ($nodeForWidth['name'] ?? '')),
                                        strlen((string) ($nodeForWidth['path'] ?? '')),
                                        strlen((string) ($nodeForWidth['destination_model'] ?? 'kein Zielmodell')),
                                        strlen('Records: ' . ((string) ($nodeForWidth['record_count'] ?? 0))),
                                        strlen('Updated: ' . ((string) ($nodeForWidth['last_updated_at'] ?? 'n/a')))
                                    );
                                    $nodeWidthForNode = max(176, min(760, (int) (($maxCharsForNode * 6.1) + 72)));
                                    $nodeWidths[$nodeForWidth['endpoint_id']] = $nodeWidthForNode;
                                    $levelMaxWidth[$levelIndex] = max($levelMaxWidth[$levelIndex], $nodeWidthForNode);
                                }
                            }
                            $levelGap = 34;
                            $nodeHeight = 78;
                            $maxRows = 1;
                            foreach ($levels as $levelNodes) {
                                $maxRows = max($maxRows, count($levelNodes));
                            }

                            $graphWidth = $paddingX * 2;
                            foreach ($levelMaxWidth as $levelWidth) {
                                $graphWidth += $levelWidth + $levelGap;
                            }
                            $graphWidth += 72;
                            $graphWidth = max(620, $graphWidth);
                            $graphHeight = max(220, ($maxRows * $rowHeight) + ($paddingY * 2));
                            $positions = [];
                            $currentX = $paddingX;
                            foreach ($levels as $levelIndex => $levelNodes) {
                                foreach (array_values($levelNodes) as $rowIndex => $node) {
                                    $positions[$node['endpoint_id']] = [
                                        'x' => $currentX,
                                        'y' => $paddingY + ($rowIndex * $rowHeight),
                                    ];
                                }
                                $currentX += ($levelMaxWidth[$levelIndex] ?? 176) + $levelGap;
                            }
                        @endphp

                        <div class="min-w-0 w-full max-w-full overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                            <div class="h-[420px] w-full max-w-full overflow-scroll p-2">
                                <div class="inline-block pr-4" style="width: {{ $graphWidth }}px; min-height: {{ $graphHeight }}px;">
                                    <svg width="{{ $graphWidth }}" height="{{ $graphHeight }}"
                                        viewBox="0 0 {{ $graphWidth }} {{ $graphHeight }}" class="block max-w-none">
                                @foreach ($levels as $levelNodes)
                                    @foreach ($levelNodes as $node)
                                        @php
                                            $parentId = $node['parent_endpoint_id'] ?? null;
                                            $from = $parentId ? ($positions[$parentId] ?? null) : null;
                                            $to = $positions[$node['endpoint_id']] ?? null;
                                            $fromWidth = $parentId ? ($nodeWidths[$parentId] ?? 176) : 176;
                                        @endphp
                                        @if ($from && $to)
                                            <line x1="{{ $from['x'] + $fromWidth }}" y1="{{ $from['y'] + ($nodeHeight / 2) }}"
                                                x2="{{ $to['x'] }}" y2="{{ $to['y'] + ($nodeHeight / 2) }}" stroke="#94a3b8"
                                                stroke-width="1.5" />
                                        @endif
                                    @endforeach
                                @endforeach

                                @foreach ($levels as $levelNodes)
                                    @foreach ($levelNodes as $node)
                                        @php
                                            $pos = $positions[$node['endpoint_id']];
                                            $nodeWidth = $nodeWidths[$node['endpoint_id']] ?? 176;
                                            $modelText = $node['destination_model'] ?: 'kein Zielmodell';
                                            $recordsText = 'Records: ' . ((string) ($node['record_count'] ?? 0));
                                            $updatedText = 'Updated: ' . ((string) ($node['last_updated_at'] ?? 'n/a'));
                                            $methodText = (string) ($node['method'] ?? '-');
                                        @endphp
                                        <rect x="{{ $pos['x'] }}" y="{{ $pos['y'] }}" width="{{ $nodeWidth }}"
                                            height="{{ $nodeHeight }}" rx="8" fill="white" stroke="#cbd5e1" />
                                        <rect x="{{ $pos['x'] + 8 }}" y="{{ $pos['y'] + 8 }}" width="34" height="14" rx="7"
                                            fill="#e2e8f0" />
                                        <text x="{{ $pos['x'] + 15 }}" y="{{ $pos['y'] + 18 }}" fill="#334155" font-size="9"
                                            font-weight="700">
                                            {{ $methodText }}
                                        </text>
                                        <text x="{{ $pos['x'] + 46 }}" y="{{ $pos['y'] + 18 }}" fill="#0f172a" font-size="11"
                                            font-weight="600">
                                            {{ $node['name'] }}
                                        </text>
                                        <text x="{{ $pos['x'] + 8 }}" y="{{ $pos['y'] + 34 }}" fill="#475569" font-size="10">
                                            {{ $node['path'] }}
                                        </text>
                                        <text x="{{ $pos['x'] + 8 }}" y="{{ $pos['y'] + 48 }}" fill="#64748b" font-size="10">
                                            {{ $modelText }}
                                        </text>
                                        <text x="{{ $pos['x'] + 8 }}" y="{{ $pos['y'] + 62 }}" fill="#64748b" font-size="10">
                                            {{ $recordsText }}
                                        </text>
                                        <text x="{{ $pos['x'] + 8 }}" y="{{ $pos['y'] + 74 }}" fill="#64748b" font-size="10">
                                            {{ $updatedText }}
                                        </text>
                                    @endforeach
                                @endforeach
                                    </svg>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        </div>
    </x-filament::card>
</x-filament::widget>