 <div id="icon-detail" class="relative max-w-screen-xl px-4 mx-auto mt-6 sm:px-6">
        <h3 class="text-3xl font-extrabold leading-8 tracking-tight text-gray-900 font-hind sm:text-5xl sm:leading-10">
            <a class="text-scarlet-600 hover:text-scarlet-500" :href="$icon->set->repository">
                {{ $icon->set->name }}
            </a>

            <br class="sm:hidden"> / {{ $icon->name }}
        </h3>

        <div class="w-full mt-6 sm:grid sm:grid-cols-5 sm:gap-10">
            <div class="flex items-center justify-center w-full py-12 text-gray-700 bg-gray-100 sm:col-span-3">
                {{ svg($icon->name, 'w-64 h-64') }}
            </div>



        @if (count($icons))
            <div class="mt-10">
                <h4 class="text-2xl font-semibold leading-6 tracking-tight text-gray-900 font-hind sm:text-3xl sm:leading-8">
                    Similar icons
                </h4>

                <div class="grid grid-cols-2 gap-3 mt-5 text-sm gap-y-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-8">
                    @foreach ($icons as $icon)
                        <div
                            class="flex flex-col items-center"
                            wire:key="result_{{$icon->id}}"
                        >
                            <a  href="{{ route('icons', $icon) }}"
                            class="flex flex-col items-center justify-between w-full h-full p-3 text-gray-500 transition duration-300 ease-in-out border border-gray-200 rounded-lg hover:text-scarlet-500 hover:shadow-md">
                            <span class="max-w-full mt-3 text-center truncate">
                                {{ $icon->name }}
                            </span>
                        </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

