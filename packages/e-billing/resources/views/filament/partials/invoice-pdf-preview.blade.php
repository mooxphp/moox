@php
    $document = $invoice->ebillingDocument;
    $source = $document?->source;
    $hasZugferdDownload = filled($document?->zugferd_storage_path) || filled($source?->zugferd_storage_path);
    $hasXmlDownload = filled($document?->xml_storage_path) || filled($source?->xml_storage_path);
@endphp
<div
    class="flex min-h-64 h-full flex-col rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/40">
    @if($hasZugferdDownload || $hasXmlDownload)
        <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
            @if($hasZugferdDownload && $source instanceof \Moox\MailInbox\Models\InboxAttachment)
                <a href="{{ route('ebilling.zugferd.download', $source) }}"
                    class="inline-flex items-center gap-1 rounded-lg bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 no-underline hover:bg-gray-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400 transition-colors dark:bg-gray-600/60 dark:text-gray-200 dark:hover:bg-gray-600">
                    <span aria-hidden="true">↓</span> {{ __('e-billing::fields.download_zugferd_pdf') }}
                </a>
            @endif

            @if($hasXmlDownload && $source instanceof \Moox\MailInbox\Models\InboxAttachment)
                <a href="{{ route('ebilling.xml.download', $source) }}"
                    class="inline-flex items-center gap-1 rounded-lg bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 no-underline hover:bg-gray-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400 transition-colors dark:bg-gray-600/60 dark:text-gray-200 dark:hover:bg-gray-600">
                    <span aria-hidden="true">↓</span> {{ __('e-billing::fields.download_xml') }}
                </a>
            @endif
        </div>
    @endif

    @if($source instanceof \Moox\MailInbox\Models\InboxAttachment)
        <iframe
            title="{{ __('e-billing::fields.preview_pdf_title') }}"
            src="{{ route('ebilling.pdf.preview', $source) }}#toolbar=0"
            class="min-h-[600px] h-[calc(100vh-280px)] w-full flex-1 rounded-lg border border-gray-200 dark:border-gray-700"
        ></iframe>
    @else
        <div
            class="flex flex-1 items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-700/40">
            <p class="m-0 text-sm text-gray-500 dark:text-gray-400">{{ __('e-billing::fields.preview_no_original_pdf') }}</p>
        </div>
    @endif
</div>
