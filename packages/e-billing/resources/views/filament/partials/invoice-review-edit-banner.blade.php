{{-- Mixed review workspace header (ADR 0004): edit mode notice, fields still to review, and customer attribution. --}}
@php($reviewFields = $page->invoiceViewModel->reviewFieldLabels())
<div
    class="mb-6 flex flex-col gap-3 rounded-lg border border-primary-500/30 bg-primary-50 p-4 text-sm
        dark:border-primary-500/30 dark:bg-primary-500/10 sm:flex-row sm:items-center sm:justify-between"
>
    <div class="min-w-0">
        <div class="font-medium text-primary-800 dark:text-primary-300">
            {{ __('e-billing::fields.review_mode_title') }}
        </div>
        <div class="mt-0.5 text-xs text-primary-700 dark:text-primary-400">
            {{ __('e-billing::fields.review_mode_hint') }}
        </div>
        @if ($reviewFields !== [])
            <div class="mt-2 text-amber-800 dark:text-amber-300">
                <div class="font-medium">{{ __('e-billing::fields.review_fields_to_check') }}</div>
                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                    @foreach ($reviewFields as $fieldLabel)
                        <li>{{ $fieldLabel }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
    <div class="flex shrink-0 flex-wrap items-center gap-2">
        <span class="text-gray-500 dark:text-gray-400">{{ __('e-billing::fields.field_customer') }}:</span>
        <span class="font-medium text-gray-900 dark:text-gray-100">
            {{ $workspace->customerLabel() ?? __('e-billing::fields.review_no_customer') }}
        </span>
        {{ $page->editAttributionAction }}
    </div>
</div>
