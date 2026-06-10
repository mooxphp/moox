@php
    /** @var \Moox\KositValidator\Models\KositValidation $record */
@endphp
<div class="w-full">
    @if (filled($record->report_html_path))
        <iframe
            src="{{ route('kosit-validator.report.html', $record) }}"
            class="h-[80vh] min-h-[600px] w-full rounded-lg border border-gray-200 dark:border-gray-700"
            sandbox="allow-same-origin"
            title="{{ __('kosit-validator::fields.validation_report') }}"
        ></iframe>
    @else
        <div class="p-4 text-sm italic text-gray-500 dark:text-gray-400">
            {{ __('kosit-validator::fields.no_report_available') }}
        </div>
    @endif
</div>
