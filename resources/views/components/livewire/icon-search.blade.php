<div class="w-full">
    <div class="relative flex items-center w-full mb-6">
        <div class="flex flex-col md:flex-row items-center border border-gray-200 rounded-lg shadow-md w-full">
            <div class="inline-block relative flex-shrink block h-full w-full md:w-auto pr-2 border-b md:border-b-0 md:border-r">
                <select
                    wire:model="set"
                    class="appearance-none bg-transparent block h-full w-full p-4 mr-4 text-xl focus:outline-none"
                >
                    <option value="">All icons</option>

                    @foreach ($sets as $set)
                        <option wire:key="set_{{ $set->id }}" value="{{ $set->id }}">
                            {{ $set->name() }}
                        </option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <x-heroicon-s-chevron-down class="fill-current h-4 w-4" />
                </div>
            </div>

            <div class="relative w-full">
                <input
                    class="block w-full p-4 text-xl border-0 rounded-lg"
                    autocapitalize="off"
                    autocomplete="off"
                    autocorrect="off"
                    spellcheck="false"
                    type="text"
                    placeholder="Search all {{ number_format($total) }} Blade icons ..."
                    wire:model.debounce.400ms="search"
                >
                <div class="absolute inset-y-0 right-0 flex items-center justify-center mr-5">
                    <div wire:loading>
                        <x-icon-refresh class="inline w-6 h-6 text-scarlet-600 fill-current animate-spin"/>
                    </div>

                    <div wire:loading.remove>
                        <button wire:click="resetSearch">
                            @if ($search)
                                <x-icon-close class="inline w-6 h-6 text-gray-500 transition duration-300 ease-in-out fill-current hover:text-scarlet-500"/>
                            @else
                                <x-icon-refresh class="inline w-6 h-6 text-scarlet-600 transition duration-300 ease-in-out fill-current hover:text-scarlet-500"/>
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div>
        @if ($search)
            <x-p>
                <span class="text-gray-500">Found:</span> {{ trans_choice('app.icons-result', count($icons)) }}
            </x-p>
        @endif

        <div class="mt-5 grid gap-3 gap-y-3 grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-10 text-sm">
            @foreach ($icons as $icon)
                <div
                    class="flex flex-col items-center"
                    wire:key="result_{{ $icon->id }}"
                >
                    <x-icon-link :icon="$icon" />
                </div>
            @endforeach
        </div>
    </div>
</div>
