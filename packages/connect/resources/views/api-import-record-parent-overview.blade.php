@extends('connect::layouts.api-import-record')

@section('title', 'Connect Parent Uebersicht - ' . $parentKey)

@section('content')
    @if (!is_null($parentEndpoint))
        <h1>Connect Endpoint-Parent Uebersicht - Endpoint #{{ $parentEndpoint['id'] }}</h1>
        <div class="subtitle">
            Parent Endpoint: {{ $parentEndpoint['method'] ?? '-' }} {{ $parentEndpoint['path'] ?? '-' }} |
            {{ $parentEndpoint['name'] ?? 'ohne Name' }}
            @if (is_string($parentKey) && $parentKey !== '')
                <br>Filter parent_key: {{ $parentKey }}
            @endif
        </div>
    @else
        <h1>Connect Parent Uebersicht - {{ $parentKey }}</h1>
        <div class="subtitle">
            Klickbare Uebersicht pro Endpoint. Von hier aus kannst du in die Detail-Show wechseln (by id / by external key).
        </div>
    @endif

    @if (($overview ?? collect())->isEmpty())
        <section class="card">
            <div class="meta">Keine Datensaetze fuer diesen Parent-Key gefunden.</div>
        </section>
    @else
        <section class="card">
            <h2>Stats</h2>
            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                <div class="card">
                    <div class="meta">Endpoints total</div>
                    <div><strong>{{ $stats['endpoints_total'] ?? 0 }}</strong></div>
                </div>
                {{-- <div class="card">
                    <div class="meta">Endpoints OK / Error / Unknown</div>
                    <div>
                        <span class="status-badge status-ok">{{ $stats['endpoints_ok'] ?? 0 }} OK</span>
                        <span class="status-badge status-error">{{ $stats['endpoints_error'] ?? 0 }} ERROR</span>
                        <span class="status-badge status-unknown">{{ $stats['endpoints_unknown'] ?? 0 }} UNKNOWN</span>
                    </div>
                </div> --}}

                <div class="card">
                    <div class="meta">Queue / Job (current route scope)</div>
                    <div><strong>{{ $stats['queue_name'] ?? 'default' }}</strong></div>
                    <div class="meta">{{ $stats['job_class'] ?? '-' }}</div>
                    <div class="meta">
                        queued jobs: <strong>{{ $stats['queued_jobs_total'] ?? 0 }}</strong>
                    </div>
                </div>
                <div class="card">
                    <div class="meta">Import records total</div>
                    <div><strong>{{ $stats['records_total'] ?? 0 }}</strong></div>
                </div>
                <div class="card">
                    <div class="meta">Import status count</div>
                    <div>
                        <span class="status-badge status-ok">{{ $stats['records_processed'] ?? 0 }} processed</span>
                        <span class="status-badge status-unknown">{{ $stats['records_new'] ?? 0 }} new</span>
                        <span class="status-badge status-error">{{ $stats['records_failed'] ?? 0 }} failed</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- <section class="card">
            <h2>Endpoint Übersicht</h2>
            <div class="grid">
                @foreach ($overview as $endpoint)
                <article class="card">
                    <div class="meta">Endpoint #{{ $endpoint['endpoint_id'] }}</div>
                    <div>{{ $endpoint['endpoint_method'] ?? '-' }} {{ $endpoint['endpoint_path'] ?? '-' }}</div>
                    <div class="meta">{{ $endpoint['endpoint_name'] ?? 'ohne Endpoint-Name' }}</div>
                    <div class="meta">
                        Einträge für diese Route/Endpoint: <strong>{{ count($endpoint['records'] ?? []) }}</strong>
                    </div>
                </article>
                @endforeach
            </div>
        </section> --}}

        <section class="card">
            <h2>Endpoint Details</h2>
            <div class="grid">
                @foreach ($overview as $endpoint)
                    <article class="card">
                        <details @if ($loop->first) @endif>
                            <summary class="meta" style="cursor: pointer;">
                                Endpoint: <strong>{{ $endpoint['endpoint_name'] }}</strong>
                                ({{ $endpoint['records_total'] ?? count($endpoint['records'] ?? []) }})
                            </summary>
                            <div style="margin-top: 6px;">
                                <div>{{ $endpoint['endpoint_method'] ?? '-' }} {{ $endpoint['endpoint_path'] ?? '-' }}</div>
                                <div class="meta">{{ $endpoint['endpoint_name'] ?? 'ohne Endpoint-Name' }}</div>
                                <div class="meta">
                                    Einträge für diese Route/Endpoint:
                                    <strong>{{ $endpoint['records_total'] ?? count($endpoint['records'] ?? []) }}</strong>
                                    @if (!empty($endpoint['records_truncated']))
                                        <span class="meta">(zeigt letzte {{ count($endpoint['records'] ?? []) }})</span>
                                    @endif
                                </div>
                                <div class="meta">
                                    queue=<strong>{{ $endpoint['queue_name'] ?? ($stats['queue_name'] ?? 'default') }}</strong>
                                    |
                                    job=<strong>{{ class_basename((string) ($endpoint['job_class'] ?? ($stats['job_class'] ?? '-'))) }}</strong>
                                    | queued_fuer_route=<strong>{{ $endpoint['queued_jobs_count'] ?? 0 }}</strong>
                                </div>
                            </div>

                            @if (!is_null($endpoint['latest_log_status_code']))
                                @php
                                    $logStatusClass = ($endpoint['latest_log_ok'] ?? null) === true
                                        ? 'status-ok'
                                        : (($endpoint['latest_log_ok'] ?? null) === false ? 'status-error' : 'status-unknown');
                                @endphp
                                <div class="meta">
                                    Endpoint: letzter API-Log-Status:
                                    <span class="status-badge {{ $logStatusClass }}">
                                        {{ $endpoint['latest_log_status_code'] }}
                                        @if (($endpoint['latest_log_ok'] ?? null) === true)
                                            OK
                                        @elseif (($endpoint['latest_log_ok'] ?? null) === false)
                                            ERROR
                                        @endif
                                    </span>
                                    @if (!empty($endpoint['latest_log_created_at']))
                                        - {{ $endpoint['latest_log_created_at'] }}
                                    @endif
                                </div>
                            @endif

                            @if (!empty($endpoint['latest_log_error']))
                                <div class="meta">API-Log Error: {{ $endpoint['latest_log_error'] }}</div>
                            @endif

                            <div class="grid" style="margin-top: 8px;">
                                @foreach ($endpoint['records'] as $record)
                                    <article class="card">
                                        @php
                                            $routeMethod = $endpoint['endpoint_method'] ?? null;
                                            $routePath = $endpoint['endpoint_path'] ?? null;
                                            $routeLabel = trim((string) ($routeMethod ? $routeMethod . ' ' : '') . (string) ($routePath ?? '-'));

                                            $countForExternalKeyRoute = collect($endpoint['external_route_counts'] ?? [])
                                                ->first(function (array $item) use ($record, $routeMethod, $routePath): bool {
                                                    return ($item['external_key'] ?? null) === ($record['external_key'] ?? null)
                                                        && ($item['route_method'] ?? null) === $routeMethod
                                                        && ($item['route_path'] ?? null) === $routePath;
                                                })['count'] ?? 0;

                                            $routesForExternalKey = collect($overview ?? [])
                                                ->flatMap(function (array $overviewEndpoint): array {
                                                    return $overviewEndpoint['external_route_counts'] ?? [];
                                                })
                                                ->filter(function (array $item) use ($record): bool {
                                                    return ($item['external_key'] ?? null) === ($record['external_key'] ?? null);
                                                })
                                                ->map(function (array $item): array {
                                                    $method = (string) ($item['route_method'] ?? '');
                                                    $path = (string) ($item['route_path'] ?? '-');

                                                    return [
                                                        'label' => trim(($method !== '' ? $method . ' ' : '') . $path),
                                                        'count' => (int) ($item['count'] ?? 0),
                                                    ];
                                                })
                                                ->sortByDesc('count')
                                                ->values();
                                        @endphp
                                        <details>
                                            <summary class="meta" style="cursor: pointer;">
                                                Import #{{ $record['id'] }} ({{ $countForExternalKeyRoute }})
                                            </summary>
                                            <div style="margin-top: 6px;">
                                                <div class="meta">
                                                    record_status=<span
                                                        class="status-badge {{ ($record['status'] ?? '') === 'failed' ? 'status-error' : 'status-ok' }}">{{ $record['status'] ?? '-' }}</span>
                                                    @if (!empty($record['created_at']))
                                                        | {{ $record['created_at'] }}
                                                    @endif
                                                </div>
                                                <div class="meta">
                                                    route=<strong>{{ $routeLabel }}</strong>
                                                    | eintraege_fuer_route_und_key=<strong>{{ $countForExternalKeyRoute }}</strong>
                                                </div>
                                            </div>
                                            @if (is_string($record['show_by_external'] ?? null))
                                                <div class="meta">
                                                    <a href="{{ $record['show_by_external'] }}">Show by external key
                                                        ({{ $record['external_key'] }})</a>
                                                </div>
                                            @endif
                                            @if ($routesForExternalKey->isNotEmpty())
                                                <div class="meta">
                                                    Enpoint:
                                                    @foreach ($routesForExternalKey as $idx => $routeItem)
                                                        @if ($idx > 0)
                                                            ,
                                                        @endif
                                                        <strong>{{ $routeItem['label'] }}</strong> ({{ $routeItem['count'] }})
                                                    @endforeach
                                                </div>
                                            @endif
                                        </details>
                                    </article>
                                @endforeach
                            </div>
                        </details>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endsection