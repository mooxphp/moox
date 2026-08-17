@php
    $document = $invoice->ebillingDocument;
    $hasOriginalPreview = filled($document?->sourceStoragePath());
    $deliverable = $document?->isDeliverable() ?? false;
    $hasZugferdDownload = $deliverable && filled($document?->pdf_storage_path);
    $hasXmlDownload = $deliverable && filled($document?->xml_storage_path);
    $hasCopyDownload = $deliverable && filled($document?->copy_pdf_storage_path);
@endphp
<div
    class="flex min-h-64 h-full flex-col rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/40">
    @if($hasZugferdDownload || $hasXmlDownload || $hasCopyDownload)
        <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
            @if($hasZugferdDownload && $document)
                <a href="{{ route('ebilling.zugferd.download', $document) }}"
                    class="inline-flex items-center gap-1 rounded-lg bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 no-underline hover:bg-gray-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400 transition-colors dark:bg-gray-600/60 dark:text-gray-200 dark:hover:bg-gray-600">
                    <span aria-hidden="true">↓</span> {{ __('e-billing::fields.download_zugferd_pdf') }}
                </a>
            @endif

            @if($hasCopyDownload && $document)
                <a href="{{ route('ebilling.copy.download', $document) }}"
                    class="inline-flex items-center gap-1 rounded-lg bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 no-underline hover:bg-gray-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400 transition-colors dark:bg-gray-600/60 dark:text-gray-200 dark:hover:bg-gray-600">
                    <span aria-hidden="true">↓</span> {{ __('e-billing::fields.download_copy_pdf') }}
                </a>
            @endif

            @if($hasXmlDownload && $document)
                <a href="{{ route('ebilling.xml.download', $document) }}"
                    class="inline-flex items-center gap-1 rounded-lg bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 no-underline hover:bg-gray-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400 transition-colors dark:bg-gray-600/60 dark:text-gray-200 dark:hover:bg-gray-600">
                    <span aria-hidden="true">↓</span> {{ __('e-billing::fields.download_xml') }}
                </a>
            @endif
        </div>
    @endif

    @if($hasOriginalPreview && $document)
        <iframe
            title="{{ __('e-billing::fields.preview_pdf_title') }}"
            src="{{ route('ebilling.pdf.preview', $document) }}#toolbar=0"
            class="min-h-[600px] h-[calc(100vh-280px)] w-full flex-1 rounded-lg border border-gray-200 dark:border-gray-700"
        ></iframe>
    @else
        <div
            class="flex flex-1 items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-700/40">
            <p class="m-0 text-sm text-gray-500 dark:text-gray-400">{{ __('e-billing::fields.preview_no_original_pdf') }}</p>
        </div>
    @endif
</div>
