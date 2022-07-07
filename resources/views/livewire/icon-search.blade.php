<div class="w-full">
    <div class="relative flex items-center w-full mb-6">
        <div class="flex flex-col items-center w-full border border-gray-200 rounded-lg shadow-md md:flex-row">
            <div class="relative flex-shrink block inline-block w-full h-full pr-2 border-b md:w-auto md:border-b-0 md:border-r">
                <select
                    wire:model="set"
                    class="block w-full h-full p-4 mr-4 text-xl bg-transparent appearance-none focus:outline-none"
                >
                    <option value="">All icons</option>

                    @foreach ($sets as $set)
                        <option wire:key="set_{{ $set->id }}" value="{{ $set->id }}">
                            {{ $set->name() }}
                        </option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 pointer-events-none">
                    <x-heroicon-s-chevron-down class="w-4 h-4 fill-current" />
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
                    placeholder="Search all {{ number_format($total) }} Hi Blade icons ..."
                    wire:model.debounce.400ms="search"
                >
            </div>
        </div>
    </div>

    <div>
        @if ($search)
            <x-p>
                <span class="text-gray-500">Found:</span> {{ trans_choice('app.icons-result', count($icons)) }}
            </x-p>
        @endif

        <div class="grid grid-cols-2 gap-3 mt-5 text-sm gap-y-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-10">
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


{{-- <div class="w-full">
    <div class="relative flex items-center w-full mb-6">
        <div class="flex flex-col items-center w-full border border-gray-200 rounded-lg shadow-md md:flex-row">
            <div class="relative flex-shrink block inline-block w-full h-full pr-2 border-b md:w-auto md:border-b-0 md:border-r">
                <select
                    wire:model="set"
                    class="block w-full h-full p-4 mr-4 text-xl bg-transparent appearance-none focus:outline-none"
                >
                    <option value="">All icons</option>

                    @foreach ($sets as $set)
                        <option wire:key="set_{{ $set->id }}" value="{{ $set->id }}">
                            {{ $set->name() }}
                        </option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 pointer-events-none">
                    <x-heroicon-s-chevron-down class="w-4 h-4 fill-current" />
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
                    placeholder="Search all {{ number_format($total) }} Hi Blade icons ..."
                    wire:model.debounce.400ms="search"
                >
                <div class="absolute inset-y-0 right-0 flex items-center justify-center mr-5">
                    <div wire:loading>
                        {{-- x-icon-refresh class="inline w-6 h-6 fill-current text-scarlet-600 animate-spin"/ --}}
                    {{-- </div>

                    <div wire:loading.remove>
                        <button wire:click="resetSearch">
                            @if ($search) --}}
                                {{-- x-icon-close class="inline w-6 h-6 text-gray-500 transition duration-300 ease-in-out fill-current hover:text-scarlet-500"/ --}}
                            {{-- @else --}}
                                {{-- <x-icon-refresh class="inline w-6 h-6 transition duration-300 ease-in-out fill-current text-scarlet-600 hover:text-scarlet-500"/> --}}
                            {{-- @endif --}}
                        {{-- </button>
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

        <div class="grid grid-cols-2 gap-3 mt-5 text-sm gap-y-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-10">
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
</div> --}}
