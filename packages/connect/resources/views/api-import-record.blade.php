@extends('connect::layouts.api-import-record')

@php
    /** @var \Moox\Connect\Models\ApiImportRecord $apiImportRecord */
    $title = 'Connect - Import Record #' . ($apiImportRecord->id ?? '-');
@endphp

@section('title', $title)

@section('content')
    <h1>Connect Debug - Scope {{ $scopeExternalKey ?? '-' }}</h1>
    <div class="subtitle">
        Overview of import records for this scope, including endpoint, import status, and latest API log status.
    </div>

    <div class="grid two">
        <section class="card">
            <h2>Summary</h2>
            @php
                $mainStatus = strtolower((string) ($apiImportRecord->status ?? ''));
                $mainStatusClass = $mainStatus === 'processed' || $mainStatus === 'new'
                    ? 'status-ok'
                    : ($mainStatus === 'failed' ? 'status-error' : 'status-unknown');
            @endphp
            <div class="meta">
                Import-Record-Status:
                <span class="status-badge {{ $mainStatusClass }}">{{ $apiImportRecord->status ?? '-' }}</span>
            </div>
            <div class="meta">API Endpoint ID: {{ $apiImportRecord->api_endpoint_id }}</div>
            <div class="meta">External key: {{ $apiImportRecord->external_key ?? '-' }}</div>
            <div class="meta">Created at: {{ $apiImportRecord->created_at?->toDateTimeString() }}</div>
            @if (is_string($apiImportRecord->external_key) && $apiImportRecord->external_key !== '')
                <div class="meta">Route (by external key):
                    <code>{{ route('connect.import-records.show', ['externalKey' => $apiImportRecord->external_key]) }}</code>
                </div>
            @endif
            @if (!empty($apiImportRecord->error_message))
                <h3>Error</h3>
                <pre>{{ $apiImportRecord->error_message }}</pre>
            @endif
        </section>

        <section class="card">
            <h2>Debug</h2>
            <ul class="meta">
                <li>payload.chunked={{ is_null($recordPayloadChunked) ? '-' : (string) $recordPayloadChunked }}</li>
                <li>payload.strategy={{ is_null($recordPayloadStrategy) ? '-' : (string) $recordPayloadStrategy }}</li>
                <li>chunks (all)={{ $chunksCountAll }}</li>
                <li>external_key candidates={{ $externalKeyCandidatesCount }}</li>
            </ul>
            @if (!empty($externalKeyCandidatesSample))
                <div class="meta">sample: <span class="mono">{{ implode(', ', $externalKeyCandidatesSample) }}</span></div>
            @endif
            <div class="meta">max_chars={{ $maxChars }}</div>
        </section>
    </div>

    <section class="card" style="margin-top: 12px;">
        <h2>Endpoint Uebersicht</h2>
        @if (($endpointOverview ?? collect())->isEmpty())
            <div class="meta">Keine Import-Eintraege fuer diesen Scope gefunden.</div>
        @else
            <div class="grid">
                @foreach ($endpointOverview as $endpoint)
                    <article class="card">
                        <div class="meta">Endpoint #{{ $endpoint['endpoint_id'] }}</div>
                        <div>{{ $endpoint['endpoint_method'] ?? '-' }} {{ $endpoint['endpoint_path'] ?? '-' }}</div>
                        <div class="meta">{{ $endpoint['endpoint_name'] ?? 'ohne Endpoint-Name' }}</div>

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
                        @else
                            <div class="meta">Kein API-Log-Status gefunden.</div>
                        @endif

                        @if (!empty($endpoint['latest_log_error']))
                            <div class="meta">API-Log Error: {{ $endpoint['latest_log_error'] }}</div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="card" style="margin-top: 12px;">
        <h2>Endpoint Details</h2>
        @if (($endpointOverview ?? collect())->isEmpty())
            <div class="meta">Keine Details vorhanden.</div>
        @else
            <div class="grid">
                @foreach ($endpointOverview as $endpoint)
                    <article class="card">
                        <h3>
                            Endpoint #{{ $endpoint['endpoint_id'] }} -
                            {{ $endpoint['endpoint_name'] ?? 'ohne Endpoint-Name' }}
                        </h3>
                        <div class="meta">{{ $endpoint['endpoint_method'] ?? '-' }} {{ $endpoint['endpoint_path'] ?? '-' }}</div>

                        <div class="grid" style="margin-top: 8px;">
                            @foreach ($endpoint['records'] as $row)
                                <article class="card">
                                    @php
                                        $importStatus = strtolower((string) ($row['status'] ?? ''));
                                        $importStatusClass = $importStatus === 'processed' || $importStatus === 'new'
                                            ? 'status-ok'
                                            : ($importStatus === 'failed' ? 'status-error' : 'status-unknown');
                                    @endphp
                                    <div class="meta">
                                        Import #{{ $row['id'] }}
                                        | record_status=<span
                                            class="status-badge {{ $importStatusClass }}">{{ $row['status'] ?? '-' }}</span>
                                        @if (!empty($row['created_at']))
                                            | {{ $row['created_at'] }}
                                        @endif
                                    </div>
                                    @if (is_string($row['route_by_external_key'] ?? null))
                                        <div class="meta">route by external key: <code>{{ $row['route_by_external_key'] }}</code></div>
                                    @endif
                                    @if (!empty($row['error_message']))
                                        <div class="meta">record error: {{ $row['error_message'] }}</div>
                                    @endif
                                    @if (($row['binary_preview']['is_image'] ?? false) && is_string($row['binary_preview']['data_url'] ?? null))
                                        <div class="preview-box">
                                            <div class="meta">Image preview @if (!empty($row['binary_preview']['file_name']))
                                            ({{ $row['binary_preview']['file_name'] }}) @endif</div>
                                            <img src="{{ $row['binary_preview']['data_url'] }}"
                                                alt="{{ $row['binary_preview']['file_name'] ?? 'Preview image' }}">
                                        </div>
                                    @endif
                                    @if (($row['binary_preview']['is_pdf'] ?? false) && is_string($row['binary_preview']['data_url'] ?? null))
                                        <div class="preview-box">
                                            <div class="meta">PDF preview @if (!empty($row['binary_preview']['file_name']))
                                            ({{ $row['binary_preview']['file_name'] }}) @endif</div>
                                            <iframe src="{{ $row['binary_preview']['data_url'] }}"></iframe>
                                        </div>
                                    @endif
                                    <details>
                                        <summary class="meta">Payload anzeigen</summary>
                                        <pre>{{ $row['payload_pretty'] }}</pre>
                                    </details>
                                </article>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <div class="meta" style="margin-top: 12px;">
        Tips: use query params like <code>max_chars</code>, <code>max_chunks</code>, <code>max_linked</code>.
    </div>
@endsection