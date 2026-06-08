@php
    $notes = $viewModel->notes();
@endphp
@if(count($notes) > 0)
    <div
        class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/40">
        <h2 class="mb-3 text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('e-billing::fields.section_notes') }}</h2>
        <div class="flex flex-col gap-3">
            @foreach($notes as $note)
                <p
                    class="m-0 rounded-lg bg-gray-50 p-3 text-sm text-gray-800 dark:bg-gray-700/40 dark:text-gray-200">
                    {{ $note }}</p>
            @endforeach
        </div>
    </div>
@endif
