<x-layout title="Blade Icons">
    <x-navigation/>

    <div id="blade-icons" class="relative bg-gray-50 overflow-hidden">
        <div class="max-w-screen-xl mx-auto">
            <div class="relative z-10 pb-8 bg-gray-50 sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-36">
                <svg class="hidden lg:block absolute right-0 inset-y-0 h-full w-32 text-gray-50 transform translate-x-1/2" fill="currentColor" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <polygon points="50,0 100,0 50,100 0,100" />
                </svg>

                <main class="mx-auto max-w-screen-xl px-4 sm:px-6 lg:px-8 pt-8 lg:pt-28 xl:pt-32">
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

        <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2 bg-scarlet-300 h-full w-full">
            <div class="h-full" style="background: url('/images/heroicons-pattern.svg') 0 13px repeat"></div>
        </div>
    </div>

    <div class="mt-16 max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center">
            <x-h3>
                Search for an icon
            </x-h3>
            <x-p>
                With {{ App\Models\IconSet::count() }} different icon sets, we probably can find the right one for you.
            </x-p>
        </div>
    </div>

    <div id="search" class="relative flex items-center justify-between max-w-screen-2xl px-4 mt-6 p-8 sm:mt-0 mx-auto sm:px-6">
        <livewire:icon-search/>
    </div>

    <x-footer/>
</x-layout>
