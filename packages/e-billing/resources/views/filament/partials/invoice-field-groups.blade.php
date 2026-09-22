@php
    $groups = $viewModel->groupedFields();
@endphp
@forelse($groups as $groupKey => $group)
    <details
        @if($group['open']) open @endif
        class="group mb-3 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800/40">
        <summary
            class="cursor-pointer list-none px-4 py-3 [&::-webkit-details-marker]:hidden [&::marker]:hidden">
            @include('e-billing::filament.partials.invoice-details-summary', [
                'title' => $group['title'],
                'subtitle' => $group['subtitle'] !== '' ? $group['subtitle'] : null,
                'issueCount' => $group['issue_count'],
                'issueLabel' => $group['issue_label'],
            ])
        </summary>
        <div class="border-t border-gray-200 px-4 pt-2 pb-4 dark:border-gray-700">
            <dl class="m-0 flex flex-col gap-0">
                @foreach($group['fields'] as $field)
                    @include('e-billing::filament.partials.invoice-field-row', ['field' => $field])
                @endforeach
            </dl>
        </div>
    </details>
    @if($groupKey === 'delivery')
        @include('e-billing::filament.partials.invoice-notes', ['viewModel' => $viewModel])
    @endif
@empty
    <div
        class="rounded-xl border border-gray-200 bg-white px-8 py-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800/40 dark:text-gray-400">
        {{ __('e-billing::fields.empty_no_field_data') }}
    </div>
@endforelse
