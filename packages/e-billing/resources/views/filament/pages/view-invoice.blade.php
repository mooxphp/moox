<x-filament-panels::page>
    @include('e-billing::filament.partials.invoice-status-banner', ['viewModel' => $this->invoiceViewModel])

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="flex flex-col gap-6 overflow-y-auto lg:max-h-[calc(100vh-12rem)]">
            @include('e-billing::filament.partials.invoice-field-groups', ['viewModel' => $this->invoiceViewModel])

            <div
                class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/40">
                <h2
                    class="mb-3 flex items-baseline gap-2 text-base font-semibold text-gray-900 dark:text-gray-100">
                    <span>{{ __('e-billing::fields.section_line_items') }}</span>
                    <span class="text-xs font-normal text-gray-400 dark:text-gray-500">BG-25</span>
                </h2>
                @include('e-billing::filament.partials.invoice-line-table', ['viewModel' => $this->invoiceViewModel])
            </div>

            @include('e-billing::filament.partials.invoice-notes', ['viewModel' => $this->invoiceViewModel])
        </div>

        <div class="sticky top-4 lg:max-h-[calc(100vh-12rem)]">
            @include('e-billing::filament.partials.invoice-pdf-preview', [
                'invoice' => $this->record,
            ])
        </div>
    </div>
</x-filament-panels::page>
