<div class="w-full">
    @if (filled($previewUrl))
        <iframe
            src="{{ $previewUrl }}"
            class="h-[80vh] min-h-[600px] w-full rounded-lg border border-gray-200 bg-white dark:border-gray-700"
            title="{{ $title }}"
        ></iframe>
    @else
        <div class="p-4 text-sm italic text-gray-500 dark:text-gray-400">
            {{ __('mail-testing::translations.preview_missing') }}
        </div>
    @endif
</div>
