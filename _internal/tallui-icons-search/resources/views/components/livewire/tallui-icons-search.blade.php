<div class="w-full">
    <div class="relative flex items-center w-full mb-6">
        <div class="flex flex-col items-center w-full border border-gray-200 rounded-lg shadow-md md:flex-row">
            <div class="relative flex-shrink block inline-block w-full h-full border-b md:w-auto md:border-b-0 ">
                <select wire:model="set"
                    class="block w-full h-full p-4 mr-4 text-xl bg-transparent appearance-none focus:outline-none">
                    <option value="">All icons</option>

                    @foreach ($sets as $set)
                        <option wire:key="set_{{ $set->id }}" value="{{ $set->id }}">
                            {{ $set->name() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="relative w-full">
                <input class="block w-full p-4 text-xl rounded-lg" autocapitalize="off" autocomplete="off"
                    autocorrect="off" spellcheck="false" type="text"
                    placeholder="Search all {{ number_format($total) }} Blade icons ..."
                    wire:model.debounce.400ms="search">
                <div class="absolute inset-y-0 right-0 flex items-center justify-center mr-5">
                    <div wire:loading>
                        <svg class="inline w-6 h-6 fill-current text-scarlet-600 animate-spin"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path
                                d="M10 3v2a5 5 0 0 0-3.54 8.54l-1.41 1.41A7 7 0 0 1 10 3zm4.95 2.05A7 7 0 0 1 10 17v-2a5 5 0 0 0 3.54-8.54l1.41-1.41zM10 20l-4-4 4-4v8zm0-12V0l4 4-4 4z" />
                        </svg>
                    </div>

                    <div wire:loading.remove>
                        <button wire:click="resetSearch">
                            @if ($search)
                                <svg class="inline w-6 h-6 text-gray-500 transition duration-300 ease-in-out fill-current hover:text-scarlet-500"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm1.41-1.41A8 8 0 1 0 15.66 4.34 8 8 0 0 0 4.34 15.66zm9.9-8.49L11.41 10l2.83 2.83-1.41 1.41L10 11.41l-2.83 2.83-1.41-1.41L8.59 10 5.76 7.17l1.41-1.41L10 8.59l2.83-2.83 1.41 1.41z" />
                                </svg>
                            @else
                                <svg class="inline w-6 h-6 transition duration-300 ease-in-out fill-current text-scarlet-600 hover:text-scarlet-500"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path
                                        d="M10 3v2a5 5 0 0 0-3.54 8.54l-1.41 1.41A7 7 0 0 1 10 3zm4.95 2.05A7 7 0 0 1 10 17v-2a5 5 0 0 0 3.54-8.54l1.41-1.41zM10 20l-4-4 4-4v8zm0-12V0l4 4-4 4z" />
                                </svg>
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div>
        @if ($search)
            <p class='mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg md:text-xl'>
                <span class="text-gray-500">Found:</span> {{ trans_choice('app.icons-result', count($icons)) }}
            </p>
        @endif

        <div class="grid grid-cols-2 gap-3 mt-5 text-sm gap-y-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-10">
            @foreach ($icons as $icon)
                <a href="{{ route('icons', $icon) }}"
                    class="flex flex-col items-center justify-between w-full h-full p-3 text-gray-500 transition duration-300 ease-in-out border border-gray-200 rounded-lg hover:text-scarlet-500 hover:shadow-md">
                    {{ svg($icon->name, 'w-8 h-8') }}

                    <span class="max-w-full mt-3 text-center truncate">
                        {{ $icon->name }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</div>
