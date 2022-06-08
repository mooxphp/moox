<div>

    <h2 class="text-lg font-semibold text-gray-900">Upcoming meetings</h2>
    <div class="lg:grid lg:grid-cols-12 lg:gap-x-16">
        <div class="mt-10 ml-10 mr-10 text-center lg:col-start-8 lg:col-end-13 lg:row-start-1 lg:mt-9 xl:col-start-9">


            <div class="flex items-center text-gray-900">

                <button wire:click.prevent="previouseMonth" type="button"
                    class="-m-1.5 flex flex-none items-center justify-center p-1.5 text-gray-400 hover:text-gray-500">
                    <span class="sr-only">Previous month</span>
                    <!-- Heroicon name: solid/chevron-left -->
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                </button>


                <div class="flex-auto font-semibold"><button class="font-semibold" wire:click="today"
                        type="button">{{ $monthname }} {{ $year }}</button></div>

                <button wire:click.prevent="nextMonth" type="button"
                    class="-m-1.5 flex flex-none items-center justify-center p-1.5 text-gray-400 hover:text-gray-500">
                    <span class="sr-only">Next month</span>
                    <!-- Heroicon name: solid/chevron-right -->
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-7 mt-6 text-xs leading-6 text-gray-500">
                <div>M</div>
                <div>T</div>
                <div>W</div>
                <div>T</div>
                <div>F</div>
                <div>S</div>
                <div>S</div>
            </div>
            <div
                class="grid grid-cols-7 gap-px mt-2 text-sm bg-gray-200 rounded-lg shadow isolate ring-1 ring-gray-200">



                @foreach ($onemonth as $day)
                    @if (isset($events))
                        @foreach ($events as $event)
                            @if (substr($day, 8, 10) == $event->event_start)
                                <button type="button"
                                    class="bg-white py-1.5 font-semibold text-indigo-600 hover:bg-gray-100 focus:z-10">
                                    <time datetime="{{ substr($day, 8, 11) }}"
                                        class="flex items-center justify-center mx-auto rounded-full h-7 w-7">{{ substr($day, 16, 2) }}</time>
                                </button>
                                @continue(2)
                            @endif
                        @endforeach
                    @endif
                    @if (substr($day, 0, 3) == 'pre')
                        <button type="button"
                            class="rounded-tl-lg bg-gray-50 py-1.5 text-gray-400 hover:bg-gray-100 focus:z-10">
                            <!--
                            Always include: "mx-auto flex h-7 w-7 items-center justify-center rounded-full"
                            Is selected and is today, include: "bg-indigo-600"
                            Is selected and is not today, include: "bg-gray-900"
                            -->
                            <time datetime="{{ substr($day, 8, 11) }}"
                                class="flex items-center justify-center mx-auto rounded-full h-7 w-7">{{ substr($day, 16, 2) }}</time>
                        </button>
                    @elseif(substr($day, 8, 10) == substr($today, 0, 10))
                        <button type="button" class="bg-white py-1.5 text-gray-900 hover:bg-gray-100 focus:z-10">
                            <time datetime="{{ substr($day, 8, 11) }}"
                                class="flex items-center justify-center mx-auto font-semibold text-white bg-gray-900 rounded-full h-7 w-7">{{ substr($day, 16, 2) }}</time>
                        </button>
                    @else
                        <button type="button" class="bg-white py-1.5 text-gray-900 hover:bg-gray-100 focus:z-10">
                            <time datetime="{{ substr($day, 8, 11) }}"
                                class="flex items-center justify-center mx-auto rounded-full h-7 w-7">{{ substr($day, 16, 2) }}</time>
                        </button>
                    @endif
                @endforeach



            </div>

            <!-- modal div -->
            <div class="" x-data="{ open: false }">
                <!-- Button (blue), duh! -->
                <button
                    class="w-full px-4 py-2 mt-8 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow focus:outline-none hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    @click="open = true">Add Event</button>
                <!-- Dialog (full screen) -->
                <div class="absolute top-0 left-0 flex items-center justify-center w-full h-full"
                    style="background-color: rgba(0,0,0,.5);" x-show="open">
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
                                        id="startEvent" type="datetime-local" placeholder="" wire:model="startEvent">
                                    <label class="block mb-2 text-sm font-bold text-gray-700" for="endEvent">
                                        End Event:
                                    </label>
                                    <input
                                        class="w-full px-3 py-2 mb-3 leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline"
                                        id="endEvent" type="datetime-local" placeholder="" wire:model="endEvent">
                                </div>
                                <div class="mb-6">
                                    <label class="block mb-2 text-sm font-bold text-gray-700">Body:
                                    </label>
                                    <textarea type="text" class="w-full px-3 py-2 mb-3 leading-tight text-gray-700 border rounded shadow appearance-none focus:outline-none focus:shadow-outline"
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


        <ol class="mt-10 text-sm leading-6 divide-y divide-gray-100 sm:mx-10 lg:col-span-7 xl:col-span-8">

            @if (isset($events))
                @foreach ($events as $event)
                    <li class="relative flex py-6 space-x-6 xl:static">
                        <div class="flex-auto">
                            <h3 class="font-semibold text-gray-900 xl:pr-0">{{ $event->subject }}</h3>
                            <dl class="flex flex-col mt-2 text-gray-500 xl:flex-row">
                                <div class="flex items-start space-x-3">
                                    <dt class="mt-0.5">
                                        <span class="sr-only">Date</span>
                                        <!-- Heroicon name: solid/calendar -->
                                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd"
                                                d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </dt>
                                    <dd><time datetime="2022-01-10T17:00">{{ $event->event_start }} at
                                            {{ $event->time_start }}</time></dd>
                                </div>
                                <div
                                    class="mt-2 flex items-start space-x-3 xl:mt-0 xl:ml-3.5 xl:border-l xl:border-gray-400 xl:border-opacity-50 xl:pl-3.5">
                                    <dt class="mt-0.5">
                                        <span class="sr-only">Location</span>
                                        <!-- Heroicon name: solid/location-marker -->
                                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd"
                                                d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </dt>
                                    <dd>Location</dd>
                                </div>
                                <div
                                    class="mt-2 flex items-start space-x-3 xl:mt-0 xl:ml-3.5 xl:border-l xl:border-gray-400 xl:border-opacity-50 xl:pl-3.5">
                                    <dt class="mt-0.5">
                                        <span class="sr-only">body</span>
                                        <!-- Heroicon name: solid/location-marker -->

                                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path
                                                d="M 4.5 2 C 3.675781 2 3 2.675781 3 3.5 L 3 14.484375 L 8 10.820313 L 13 14.484375 L 13 3.5 C 13 2.675781 12.324219 2 11.5 2 Z M 4.5 3 L 11.5 3 C 11.78125 3 12 3.21875 12 3.5 L 12 12.515625 L 8 9.578125 L 4 12.515625 L 4 3.5 C 4 3.21875 4.21875 3 4.5 3 Z">
                                            </path>
                                        </svg>
                                    </dt>
                                    <dd>{{ $event->body }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div class="absolute right-0 top-6 xl:relative xl:top-auto xl:right-auto xl:self-center">
                            <div>
                                <button type="button"
                                    class="flex items-center p-2 -m-2 text-gray-500 rounded-full hover:text-gray-600"
                                    id="menu-0-button" aria-expanded="false" aria-haspopup="true">
                                    <span class="sr-only">Open options</span>
                                    <!-- Heroicon name: solid/dots-horizontal -->
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                        fill="currentColor" aria-hidden="true">
                                        <path
                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg>
                                </button>
                            </div>

                            <!--
                  Dropdown menu, show/hide based on menu state.

                  Entering: "transition ease-out duration-100"
                    From: "transform opacity-0 scale-95"
                    To: "transform opacity-100 scale-100"
                  Leaving: "transition ease-in duration-75"
                    From: "transform opacity-100 scale-100"
                    To: "transform opacity-0 scale-95"
                -->
                            <div class="absolute right-0 z-10 hidden mt-2 origin-top-right bg-white rounded-md shadow-lg focus:outline-none w-36 ring-1 ring-black ring-opacity-5"
                                role="menu" aria-orientation="vertical" aria-labelledby="menu-0-button" tabindex="-1">
                                <div class="py-1" role="none">
                                    <!-- Active: "bg-gray-100 text-gray-900", Not Active: "text-gray-700" -->
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700" role="menuitem"
                                        tabindex="-1" id="menu-0-item-0">Edit</a>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700" role="menuitem"
                                        tabindex="-1" id="menu-0-item-1">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- More meetings... -->
                @endforeach
            @endif

        </ol>
    </div>



</div>
