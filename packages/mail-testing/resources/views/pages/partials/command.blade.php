<div
    class="rounded-xl bg-gray-50 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
    style="overflow: hidden;"
    x-data="{
        copied: false,
        copyTimer: null,
        copyLabel: {{ \Illuminate\Support\Js::from(__('mail-testing::translations.copy')) }},
        copiedLabel: {{ \Illuminate\Support\Js::from(__('mail-testing::translations.copied')) }},
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
    <div class="flex items-center justify-between gap-2 border-b border-gray-950/5 px-3 py-2 dark:border-white/10">
        <span class="min-w-0 text-sm font-medium text-gray-950 dark:text-white">{{ $label }}</span>
        <div class="flex shrink-0 items-center gap-2">
            @if (($meta ?? '') !== '')
                <span
                    class="text-xs tabular-nums"
                    @if ($metaWarn ?? false)
                        style="color: rgb(180 83 9);"
                    @else
                        style="color: rgb(107 114 128);"
                    @endif
                >{{ $meta }}</span>
            @endif
            <span
                x-cloak
                x-show="copied"
                x-transition.opacity.duration.150ms
                class="text-sm font-medium"
                style="color: light-dark(rgb(21 128 61), rgb(74 222 128));"
                aria-live="polite"
                x-text="copiedLabel"
            ></span>
            <button
                type="button"
                class="fi-icon-btn fi-size-sm"
                x-on:click="copy()"
                x-bind:aria-label="copied ? copiedLabel : copyLabel"
                x-bind:style="copied ? 'color: light-dark(rgb(21 128 61), rgb(74 222 128))' : null"
                x-tooltip="{
                    content: copied ? copiedLabel : copyLabel,
                    theme: $store.theme,
                }"
            >
                <x-filament::icon icon="heroicon-m-clipboard" x-show="! copied" />
                <x-filament::icon icon="heroicon-m-check" x-show="copied" x-cloak />
            </button>
        </div>
    </div>
    <pre
        @class([
            'overflow-auto p-3 text-xs leading-5 text-gray-700 dark:text-gray-200',
            'max-h-72' => ! ($wrap ?? false),
        ])
        @if ($wrap ?? false)
            style="max-height: 24rem; overflow: auto; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;"
        @endif
    ><code>{{ $command }}</code></pre>
</div>
