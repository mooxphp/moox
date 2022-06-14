<div>
    <!-- Dialog (full screen) -->
    <div x-show="open" x-on:click.outside="open = false" x-transition:enter="transition ease-out duration-100 transform"
        x-transition:enter-start="opacity-0 scale-30" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75 transform" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute top-0 left-0 flex items-center justify-center w-full h-full bg-gray-500"
        style="background-color: rgba(0,0,0,.5);">
        <!-- A basic modal dialog with title, body and one button to close -->
        <div class="">
            <div class="h-auto p-4 px-6 mx-2 text-left bg-white rounded shadow-xl md:max-w-xl md:p-6 lg:p-8 md:mx-0"
                @click.away="open = false">

                <form wire:submit.prevent='addEvent' class="">
                    <div class="">
                        <label class="block mb-2 text-sm font-bold text-gray-700" for="subject">
                            Subject
                        </label>
                        <input
                            class="w-full px-3 py-2 leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline"
                            id="subject" type="text" placeholder="Subject" wire:model="subject">
                    </div>
                    <div class="mb-6">
                        <label class="block mb-2 text-sm font-bold text-gray-700" for="startEvent">
                            Start Event:
                        </label>
                        <input
                            class="w-full px-3 py-2 mb-3 leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline"
                            id="startEvent" type="date" wire:model="startEvent">
                        <label class="block mb-2 text-sm font-bold text-gray-700" for="endEvent">
                            End Event:
                        </label>
                        <input
                            class="w-full px-3 py-2 mb-3 leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline"
                            id="endEvent" type="date" placeholder="" wire:model="endEvent">
                    </div>
                    <div class="mb-6">
                        <label class="block mb-2 text-sm font-bold text-gray-700">Body:
                        </label>
                        <textarea type="text"
                            class="w-full px-3 py-2 mb-3 leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline"
                            name="eventBody" rows="3" wire:model="body"></textarea>
                    </div>
                    <div class="mt-5 sm:mt-6">
                        <span class="flex w-full rounded-md shadow-sm">
                            <button @click="open = false"
                                class="inline-flex justify-center w-full px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-700">
                                Create
                            </button>
                        </span>

                    </div>
                </form>
                <div class="">
                    <span class="flex w-full rounded-md ">
                        <button @click="open = false"
                            class="inline-flex justify-center w-full px-4 py-2 text-black hover:text-gray-400">
                            Cancle
                        </button>
                    </span>

                </div>

                <!-- One big close button.  --->
            </div>
        </div>
    </div>
</div>



</div>
