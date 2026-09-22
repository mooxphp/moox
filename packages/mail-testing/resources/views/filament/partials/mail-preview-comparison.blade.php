@php
    $metricsDiffer = count($previews) > 1
        && (
            $previews[0]['byteLength'] !== $previews[1]['byteLength']
            || $previews[0]['characterLength'] !== $previews[1]['characterLength']
        );
@endphp

<div @class([
    'grid grid-cols-1 gap-4',
    'lg:grid-cols-2' => count($previews) > 1,
])>
    @foreach ($previews as $preview)
        <div class="min-w-0 space-y-2">
            <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ $preview['engine'] }}
                </h3>
                <p
                    @class([
                        'text-xs tabular-nums',
                        'text-gray-500 dark:text-gray-400' => ! $metricsDiffer,
                    ])
                    @if ($metricsDiffer)
                        style="color: rgb(180 83 9);"
                    @endif
                >
                    {{ $preview['byteLabel'] }}
                    ·
                    {{ $preview['characterLabel'] }}
                </p>
            </div>
            @include('mail-testing::filament.partials.mail-preview-iframe', [
                'previewUrl' => $preview['previewUrl'],
                'title' => $preview['engine'],
            ])
        </div>
    @endforeach
</div>
