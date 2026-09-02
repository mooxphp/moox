@php
    $noteFields = $viewModel->noteFields();
@endphp
@if(count($noteFields) > 0)
    <div
        class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/40">
        <div class="mb-4 border-b border-gray-200 pb-3 dark:border-gray-700">
            <h2
                class="flex items-baseline gap-2 text-base font-semibold text-gray-900 dark:text-gray-100">
                <span>{{ __('e-billing::fields.section_notes') }}</span>
                <span class="text-xs font-normal text-gray-400 dark:text-gray-500">BG-1 / BT-22</span>
            </h2>
        </div>
        <dl class="m-0 flex flex-col gap-0">
            @foreach($noteFields as $field)
                @include('e-billing::filament.partials.invoice-field-row', ['field' => $field])
            @endforeach
        </dl>
    </div>
@endif
