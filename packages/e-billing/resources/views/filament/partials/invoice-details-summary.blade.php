<span class="flex items-center justify-between gap-3">
    <span class="flex min-w-0 flex-wrap items-baseline gap-x-2 gap-y-1">
        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</span>
        @if(! empty($subtitle))
            <span class="text-xs font-normal text-gray-400 dark:text-gray-500">{{ $subtitle }}</span>
        @endif
        @if(($issueCount ?? 0) > 0 && filled($issueLabel ?? null))
            <span
                class="inline-flex items-center rounded-md border border-red-500/20 bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 dark:border-red-500/30 dark:bg-red-500/15 dark:text-red-300">
                {{ $issueLabel }}
            </span>
        @endif
    </span>
    <span
        class="inline-block shrink-0 text-xs font-normal text-gray-500 transition-transform duration-200 ease-in-out group-open:rotate-180 dark:text-gray-400"
        aria-hidden="true">▼</span>
</span>
