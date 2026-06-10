@php
    $groups = $viewModel->groupedFields();
@endphp
@forelse($groups as $group)
    <div
        class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/40">
        <div class="mb-4 border-b border-gray-200 pb-3 dark:border-gray-700">
            <h2
                class="flex items-baseline gap-2 text-base font-semibold text-gray-900 dark:text-gray-100">
                <span>{{ $group['title'] }}</span>
                <span class="text-xs font-normal text-gray-400 dark:text-gray-500">{{ $group['subtitle'] }}</span>
            </h2>
        </div>
        <dl class="m-0 flex flex-col gap-0">
            @foreach($group['fields'] as $field)
                @include('e-billing::filament.partials.invoice-field-row', ['field' => $field])
            @endforeach
        </dl>
    </div>
@empty
    <div
        class="rounded-xl border border-gray-200 bg-white px-8 py-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800/40 dark:text-gray-400">
        {{ __('e-billing::fields.empty_no_field_data') }}
    </div>
@endforelse
