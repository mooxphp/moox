@php
    $record = $getRecord() ?? ($record ?? null);
    $variants = $record
        ? app(\Moox\Media\Support\MediaVariantCatalog::class)->for($record)
        : [];
@endphp

@if ($record && $variants !== [])
    <div class="overflow-x-auto text-sm">
        <table style="width:100%;table-layout:fixed;border-collapse:collapse;text-align:left;">
            <colgroup>
                <col style="width:auto;">
                <col style="width:9rem;">
                <col style="width:7rem;">
                <col style="width:6rem;">
            </colgroup>
            <thead>
                <tr class="text-gray-500 dark:text-gray-400">
                    <th style="padding:0 1rem 0.5rem 0;font-weight:500;text-align:left;">{{ __('media::fields.variant') }}</th>
                    <th style="padding:0 1rem 0.5rem 0;font-weight:500;text-align:left;">{{ __('media::fields.profile') }}</th>
                    <th style="padding:0 1rem 0.5rem 0;font-weight:500;text-align:left;">{{ __('media::fields.status') }}</th>
                    <th style="padding:0 0 0.5rem 0;font-weight:500;text-align:left;">{{ __('media::fields.preview') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($variants as $variant)
                    <tr>
                        <td style="padding:0.5rem 1rem 0.5rem 0;text-align:left;" class="text-gray-950 dark:text-white">
                            <code class="text-xs">{{ $variant['key'] }}</code>
                            <div class="text-xs text-gray-500">{{ $variant['label'] }}</div>
                        </td>
                        <td style="padding:0.5rem 1rem 0.5rem 0;text-align:left;" class="text-gray-600 dark:text-gray-300">
                            {{ $variant['profile'] ?? '—' }}
                        </td>
                        <td style="padding:0.5rem 1rem 0.5rem 0;text-align:left;">
                            @if ($variant['generated'])
                                <span class="text-success-600 dark:text-success-400">{{ __('media::fields.generated') }}</span>
                            @else
                                <span class="text-gray-400">{{ __('media::fields.pending') }}</span>
                            @endif
                        </td>
                        <td style="padding:0.5rem 0;text-align:left;">
                            @if ($variant['url'])
                                <a
                                    href="{{ $variant['url'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-primary-600 hover:underline dark:text-primary-400"
                                >
                                    {{ __('media::fields.open') }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif ($record)
    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ __('media::fields.pending') }}
    </p>
@endif
