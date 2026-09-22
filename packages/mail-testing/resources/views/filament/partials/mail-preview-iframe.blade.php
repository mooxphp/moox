<div class="w-full">
    @if (filled($previewUrl))
        <iframe
            src="{{ $previewUrl }}"
            class="h-[70vh] min-h-96 w-full rounded-lg border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900"
            title="{{ $title }}"
        ></iframe>
    @else
        <x-filament::callout
            color="gray"
            icon="heroicon-o-document"
            :description="__('mail-testing::translations.preview_missing')"
        />
    @endif
</div>
