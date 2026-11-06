@php
    $record = $getRecord() ?? ($record ?? null);
    $variants = $record
        ? app(\Moox\Media\Support\MediaVariantCatalog::class)->for($record)
        : [];
@endphp

@if ($record && $variants !== [])
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 dark:text-gray-400">
                    <th class="pb-2 pr-4 font-medium">{{ __('media::fields.variant') }}</th>
                    <th class="pb-2 pr-4 font-medium">{{ __('media::fields.profile') }}</th>
                    <th class="pb-2 pr-4 font-medium">{{ __('media::fields.status') }}</th>
                    <th class="pb-2 font-medium">{{ __('media::fields.preview') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($variants as $variant)
                    <tr>
                        <td class="py-2 pr-4 text-gray-950 dark:text-white">
                            <code class="text-xs">{{ $variant['key'] }}</code>
                            <div class="text-xs text-gray-500">{{ $variant['label'] }}</div>
                        </td>
                        <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">
                            {{ $variant['profile'] ?? '—' }}
                        </td>
                        <td class="py-2 pr-4">
                            @if ($variant['generated'])
                                <span class="text-success-600 dark:text-success-400">{{ __('media::fields.generated') }}</span>
                            @else
                                <span class="text-gray-400">{{ __('media::fields.pending') }}</span>
                            @endif
                        </td>
                        <td class="py-2">
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
