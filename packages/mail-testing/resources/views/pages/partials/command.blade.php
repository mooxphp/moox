<div
    class="overflow-hidden rounded-xl bg-gray-50 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
    x-data="{
        copied: false,
        copyTimer: null,
        async copy() {
            try {
                await window.navigator.clipboard.writeText({{ \Illuminate\Support\Js::from($command) }});
            } catch (error) {
                return;
            }

            this.copied = true;
            window.clearTimeout(this.copyTimer);
            this.copyTimer = window.setTimeout(() => {
                this.copied = false;
            }, 2000);
        },
    }"
>
    <div class="flex items-center justify-between gap-2 border-b border-gray-200 px-3 py-2 dark:border-white/10">
        <span class="min-w-0 text-sm font-medium text-gray-950 dark:text-white">{{ $label }}</span>
        <div class="flex shrink-0 items-center gap-2">
            @if (($meta ?? '') !== '')
                <x-filament::badge
                    :color="($metaWarn ?? false) ? 'warning' : 'gray'"
                    size="sm"
                >
                    {{ $meta }}
                </x-filament::badge>
            @endif
            <span x-cloak x-show="copied" x-transition.opacity.duration.150ms aria-live="polite">
                <x-filament::badge color="success" icon="heroicon-m-check" size="sm">
                    {{ __('mail-testing::translations.copied') }}
                </x-filament::badge>
            </span>
            <x-filament::icon-button
                color="gray"
                icon="heroicon-m-clipboard"
                size="sm"
                :label="__('mail-testing::translations.copy')"
                :tooltip="__('mail-testing::translations.copy')"
                x-on:click="copy()"
            />
        </div>
    </div>
    <pre @class([
        'overflow-auto p-3 font-mono text-xs leading-5 text-gray-700 dark:text-gray-200',
        'max-h-72' => ! ($wrap ?? false),
        'max-h-96 whitespace-pre-wrap break-words' => (bool) ($wrap ?? false),
    ])><code>{{ $command }}</code></pre>
</div>
