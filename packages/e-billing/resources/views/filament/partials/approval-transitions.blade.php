<div
    class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/40"
>
    <h2 class="mb-3 text-base font-semibold text-gray-900 dark:text-gray-100">
        {{ __('e-billing::fields.section_approval_history') }}
    </h2>

    @if ($transitions === [])
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('e-billing::fields.approval_history_empty') }}
        </p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="py-2 pe-4">{{ __('e-billing::fields.approval_history_kind') }}</th>
                        <th class="py-2 pe-4">{{ __('e-billing::fields.approval_history_actor') }}</th>
                        <th class="py-2 pe-4">{{ __('e-billing::fields.approval_history_at') }}</th>
                        <th class="py-2">{{ __('e-billing::fields.approval_history_reason') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @foreach ($transitions as $transition)
                        <tr>
                            <td class="py-2 pe-4 font-medium text-gray-900 dark:text-gray-100">
                                {{ $transition['kind_label'] }}
                            </td>
                            <td class="py-2 pe-4 text-gray-700 dark:text-gray-300">
                                {{ $transition['actor'] }}
                            </td>
                            <td class="py-2 pe-4 text-gray-700 dark:text-gray-300">
                                {{ $transition['at_formatted'] }}
                            </td>
                            <td class="py-2 text-gray-700 dark:text-gray-300">
                                @if ($transition['reason'] !== '')
                                    {{ $transition['reason'] }}
                                @endif
                                @if ($transition['forwarded'] !== [])
                                    <ul class="mt-1 list-disc ps-4 text-xs text-gray-500 dark:text-gray-400">
                                        @foreach ($transition['forwarded'] as $release)
                                            <li>
                                                {{ $release['field'] }}:
                                                {{ $release['reason'] }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
