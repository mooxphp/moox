<x-filament-panels::page>
    <div class="space-y-6">
        @foreach ($this->getGroupedTargets() as $category => $targets)
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('moox-cache::cache.categories.' . $category, [], null, $category) }}
                </x-slot>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($targets as $target)
                        <div
                            class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ $target->label() }}
                                    </h3>
                                    @if ($target->description())
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $target->description() }}
                                        </p>
                                    @endif
                                </div>
                                <x-filament::badge :color="$target->color() ?? 'gray'">
                                    {{ $target->status()->value }}
                                </x-filament::badge>
                            </div>

                            @if ($target->key() === 'custom-key')
                                <div class="mt-3">
                                    <x-filament::input.wrapper>
                                        <x-filament::input
                                            type="text"
                                            wire:model="customKey"
                                            placeholder="{{ __('moox-cache::cache.form.cache_key') }}"
                                        />
                                    </x-filament::input.wrapper>
                                </div>
                            @endif

                            @if ($target->key() === 'page-cache-clear-slug')
                                <div class="mt-3 space-y-2">
                                    <x-filament::input.wrapper>
                                        <x-filament::input
                                            type="text"
                                            wire:model="pageCacheSlug"
                                            placeholder="{{ __('moox-cache::cache.form.page_cache_slug') }}"
                                        />
                                    </x-filament::input.wrapper>
                                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <input type="checkbox" wire:model="pageCacheRecursive" class="rounded border-gray-300" />
                                        {{ __('moox-cache::cache.form.page_cache_recursive') }}
                                    </label>
                                </div>
                            @endif

                            @if ($target->key() === 'cache-store-flush')
                                <div class="mt-3">
                                    <x-filament::input.wrapper>
                                        <x-filament::input
                                            type="text"
                                            wire:model="cacheStore"
                                            placeholder="{{ config('cache.default') }}"
                                        />
                                    </x-filament::input.wrapper>
                                </div>
                            @endif

                            <div class="mt-4">
                                <x-filament::button
                                    wire:click="clearTarget('{{ $target->key() }}')"
                                    wire:confirm="{{ __('moox-cache::cache.confirm.clear', ['target' => $target->label()]) }}"
                                    color="{{ $target->color() ?? 'danger' }}"
                                    size="sm"
                                >
                                    {{ __('moox-cache::cache.actions.clear') }}
                                </x-filament::button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endforeach

        @if (count($this->getConfiguredCacheKeys()) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('moox-cache::cache.categories.keys') }}
                </x-slot>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->getConfiguredCacheKeys() as $cacheKey)
                        <div
                            class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                        >
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $cacheKey['label'] }}
                            </h3>
                            @if (! empty($cacheKey['description']))
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $cacheKey['description'] }}
                                </p>
                            @endif
                            <p class="mt-2 font-mono text-xs text-gray-500">{{ $cacheKey['key'] }}</p>
                            <div class="mt-4">
                                <x-filament::button
                                    wire:click="$set('customKey', '{{ $cacheKey['key'] }}'); clearTarget('custom-key')"
                                    wire:confirm="{{ __('moox-cache::cache.confirm.forget_key', ['key' => $cacheKey['key']]) }}"
                                    color="warning"
                                    size="sm"
                                >
                                    {{ __('moox-cache::cache.actions.forget_key') }}
                                </x-filament::button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        @if ($lastResult)
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('moox-cache::cache.result.heading', ['target' => $lastResultTarget]) }}
                </x-slot>

                <dl class="grid gap-2 text-sm">
                    <div class="flex gap-2">
                        <dt class="font-medium text-gray-500 dark:text-gray-400">
                            {{ __('moox-cache::cache.result.status') }}
                        </dt>
                        <dd>{{ $lastResult->success ? __('moox-cache::cache.result.success') : __('moox-cache::cache.result.failure') }}</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="font-medium text-gray-500 dark:text-gray-400">
                            {{ __('moox-cache::cache.result.duration') }}
                        </dt>
                        <dd>{{ number_format($lastResult->durationMs, 2) }} ms</dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="font-medium text-gray-500 dark:text-gray-400">
                            {{ __('moox-cache::cache.result.message') }}
                        </dt>
                        <dd>{{ $lastResult->message }}</dd>
                    </div>
                </dl>

                @if ($lastResult->output)
                    <pre class="mt-4 overflow-x-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-100">{{ $lastResult->output }}</pre>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
