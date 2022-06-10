<div x-data="{ modal: true}">


    <div x-show="modal" x-cloak x-transition:enter="transition ease-out duration-100 transform"
        x-transition:enter-start="opacity-0 scale-30" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75 transform" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed top-0 bottom-0 left-0 right-0 z-50 flex items-center justify-center px-5 bg-gray-500 backdrop-blur-sm bg-opacity-70">
        <div @click.outside="modal=false" class="overflow-hidden bg-white rounded-md shadow-sm w-96">
            <div class="px-5 py-7">

                Hi
            </div>

            <div class="grid grid-cols-2 gap-4 px-4 text-right bg-gray-100 py-7 sm:px-6">

            </div>
        </div>
    </div>
</div>
