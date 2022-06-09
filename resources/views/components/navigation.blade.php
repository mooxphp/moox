<div class="relative bg-white overflow-hidden">
    <div class="relative pt-6 pb-4 sm:pb-10" x-data="{ open: false }">
        <nav class="relative max-w-screen-xl mx-auto flex items-center justify-between px-4 sm:px-6">
            <div class="flex items-center flex-1">
                <div class="flex items-center justify-between w-full md:w-auto">
                    <a href="{{ route('home') }}" aria-label="Home">
                        <img class="h-8 w-auto sm:h-10" src="{{ asset('/images/icon.svg') }}" alt="Logo" />
                    </a>
                    <div class="-mr-2 flex items-center md:hidden">
                        <button @click="open = true" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out" id="main-menu" aria-label="Main menu" aria-haspopup="true">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="hidden md:block md:ml-10">
                    <a href="{{ route('blade-icons') }}" class="ml-10 font-medium text-gray-500 hover:text-gray-900 transition duration-150 ease-in-out">
                        Blade Icons
                    </a>
                </div>
            </div>
            <div class="hidden md:block text-right text-gray">
                <x-a
                    href="https://twitter.com/bladeuikit"
                    class="ml-4 font-medium transition duration-150 ease-in-out"
                >
                </x-a>
                <x-a
                    href="https://github.com/blade-ui-kit"
                    class="ml-4 font-medium transition duration-150 ease-in-out"
                >
                </x-a>
            </div>
        </nav>

        <div class="top-0 inset-x-0 p-2 transition transform origin-top-right md:hidden fixed top-2 z-50" x-show="open" @click.away="open = false">
            <div class="rounded-lg shadow-md">
                <div class="rounded-lg bg-white ring-1 ring-black ring-opacity-5 overflow-hidden" role="menu" aria-orientation="vertical" aria-labelledby="main-menu">
                    <div class="px-5 pt-4 flex items-center justify-between">
                        <div>
                            <a href="{{ route('home') }}">
                                <img class="h-8 w-auto" src="{{ asset('/images/icon.svg') }}" alt="" />
                            </a>
                        </div>
                        <div class="-mr-2">
                            <button @click="open = false" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out" aria-label="Close menu">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="px-2 pt-2 pb-3">
                        <a href="{{ route('blade-icons') }}" class="mt-1 block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 focus:outline-none focus:text-gray-900 focus:bg-gray-50 transition duration-150 ease-in-out" role="menuitem">
                            Blade Icons
                        </a>
                    </div>
                    <div class="bg-gray-50 text-center">
                        <x-a
                            href="https://twitter.com/bladeuikit"
                            class="inline-block px-5 py-3 text-center font-medium bg-gray-50 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
                        >
                        </x-a>
                        <x-a
                            href="https://github.com/blade-ui-kit"
                            class="inline-block px-5 py-3 text-center font-medium bg-gray-50 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
                        >
                        </x-a>
                    </div>
                </div>
            </div>
        </div>

        {{ $slot }}
    </div>
</div>
