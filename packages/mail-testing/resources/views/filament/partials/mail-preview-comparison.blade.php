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
        <div class="grid min-w-0 gap-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ $preview['engine'] }}
                </h3>
                <x-filament::badge :color="$metricsDiffer ? 'warning' : 'gray'" size="sm">
                    {{ $preview['byteLabel'] }}
                    ·
                    {{ $preview['characterLabel'] }}
                </x-filament::badge>
            </div>
            @include('mail-testing::filament.partials.mail-preview-iframe', [
                'previewUrl' => $preview['previewUrl'],
                'title' => $preview['engine'],
            ])
        </div>
    @endforeach
</div>
