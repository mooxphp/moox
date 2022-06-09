<x-layout>
    <x-navigation/>

    <div id="blade-icons" class="relative overflow-hidden bg-gray-50">
        <div class="max-w-screen-xl mx-auto">
            <div class="relative z-10 pb-8 bg-gray-50 sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-36">
                <svg class="absolute inset-y-0 right-0 hidden w-32 h-full transform translate-x-1/2 lg:block text-gray-50" fill="currentColor" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <polygon points="50,0 100,0 50,100 0,100" />
                </svg>

                <main class="max-w-screen-xl px-4 pt-8 mx-auto sm:px-6 lg:px-8 lg:pt-28 xl:pt-32">
                    <div class="sm:text-center lg:text-left lg:pr-8">
                        <x-h2>
                            Blade Icons
                        </x-h2>

                        <x-p>
                            A package to easily make use of SVG icons in your <strong>Laravel Blade</strong> views. Choose from a wide selection of icon sets. <span class="hidden lg:inline">Like the <x-a href="https://github.com/blade-ui-kit/blade-heroicons">Heroicons</x-a> on the right.</span>
                        </x-p>

                        <div class="mt-6 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                            <x-buttons.primary href="https://github.com/blade-ui-kit/blade-icons">
                                Get started
                            </x-buttons.primary>

                            <div class="mt-3 sm:mt-0 sm:ml-3">
                                <x-buttons.secondary :href="route('blade-icons').'#search'">
                                    Search Icons
                                </x-buttons.secondary>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <div class="w-full h-full lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2 bg-scarlet-300">
            <div class="h-full" style="background: url('/images/heroicons-pattern.svg') 0 13px repeat"></div>
        </div>
    </div>

    <div class="px-4 mx-auto mt-16 max-w-screen-2xl sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center">
            <x-h3>
                Search for an icon
            </x-h3>
            <x-p>
                With {{ App\Models\IconSet::count() }} different icon sets, we probably can find the right one for you.
            </x-p>
        </div>
    </div>

    <div id="search" class="relative flex items-center justify-between p-8 px-4 mx-auto mt-6 max-w-screen-2xl sm:mt-0 sm:px-6">
        <livewire:icon-search/>
    </div>

    <x-footer/>
</x-layout>
