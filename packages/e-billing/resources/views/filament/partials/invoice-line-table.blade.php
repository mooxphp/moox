@php
    $lines = $viewModel->lines();
@endphp
@forelse($lines as $lineVm)
    <details
        class="group mb-3 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/40">
        <summary
            class="cursor-pointer list-none px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100 [&::-webkit-details-marker]:hidden [&::marker]:hidden">
            <span class="flex items-center justify-between gap-2">
                <span>{{ __('e-billing::fields.line_item_position', ['position' => $lineVm->position() ?? '—']) }}</span>
                <span
                    class="inline-block text-xs font-normal text-gray-500 transition-transform duration-200 ease-in-out group-open:rotate-180 dark:text-gray-400"
                    aria-hidden="true">▼</span>
            </span>
        </summary>
        <div class="border-t border-gray-200 px-4 pt-2 pb-4 dark:border-gray-700">
            <dl class="m-0 flex flex-col gap-0">
                @foreach($lineVm->relevantFields() as $field)
                    @include('e-billing::filament.partials.invoice-field-row', ['field' => $field])
                @endforeach
            </dl>
        </div>
    </details>
@empty
    <div class="px-8 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ __('e-billing::fields.empty_no_line_items') }}
    </div>
@endforelse
