@extends('layouts.app')


@section('content')
    <div class="container grid grid-cols-1 mx-auto gap-14 py-14">


        <div>
            <p class="mb-3 text-2xl text-white">Button</p>
            <div class="p-5 bg-white rounded-lg">

                <x-daisy-ui.button class="btn-xs btn-active btn-secondary">
                    Button
                </x-daisy-ui.button>

                <x-daisy-ui.button class="btn-sm btn-info">
                    Button
                </x-daisy-ui.button>

                <x-daisy-ui.button>
                    Button
                </x-daisy-ui.button>

                <x-daisy-ui.button class="btn-lg btn-primary">
                    Button Primary
                </x-daisy-ui.button>

                <x-daisy-ui.button class="btn-xl btn-accent">
                    Button
                </x-daisy-ui.button>
            </div>
        </div>



        <div>
            <p class="mb-3 text-2xl text-white">Tooltip</p>
            <div class="grid grid-cols-1 gap-5 p-5 bg-white rounded-lg">

                <div>
                    <x-moox-tooltip message="lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, quos.">
                        <x-daisy-ui.button class=" btn-active btn-secondary">
                            hover me
                        </x-daisy-ui.button>
                    </x-moox-tooltip>
                </div>

                <div>
                    <x-moox-tooltip>
                        <x-moox-tooltip-content>
                            <div class="flex gap-5">
                                <x-flag-de class="w-7 h-7" />
                                <p class="text-white">lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam,
                                    quos.</p>
                            </div>
                        </x-moox-tooltip-content>

                        <p>mit html content</p>
                    </x-moox-tooltip>
                </div>

            </div>
        </div>


        <div>
            <p class="mb-3 text-2xl text-white">Alert</p>
            <div class="grid grid-cols-1 gap-5 p-5 bg-white rounded-lg">

                <div class="grid grid-cols-1 gap-5">
                    <x-moox-alert class="text-white alert-info">
                        <x-moox-alert-content class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                class="w-6 h-6 stroke-current shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p>lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, quos.</p>
                        </x-moox-alert-content>
                    </x-moox-alert>



                    <x-moox-alert class="alert-success">
                        <x-moox-alert-content class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                class="w-6 h-6 stroke-current shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p>lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, quos.</p>
                        </x-moox-alert-content>
                    </x-moox-alert>

                    <x-moox-alert class="alert-warning alert-outline">
                        <x-moox-alert-content class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                class="w-6 h-6 stroke-current shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p>lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, quos.</p>
                        </x-moox-alert-content>
                    </x-moox-alert>

                    <x-moox-alert class="alert-error alert-soft">
                        <x-moox-alert-content class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                class="w-6 h-6 stroke-current shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p>lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, quos.</p>
                        </x-moox-alert-content>
                    </x-moox-alert>



                    <x-moox-alert class="alert-vertical sm:alert-horizontal">
                        <x-moox-alert-content class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                class="w-6 h-6 stroke-current shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p>lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, quos.</p>
                        </x-moox-alert-content>

                        <x-moox-alert-action class="flex justify-end w-full">
                            <button class="btn btn-sm">Deny</button>
                            <button class="btn btn-sm btn-primary">Accept</button>
                        </x-moox-alert-action>
                    </x-moox-alert>


                    <x-moox-alert class="alert-vertical sm:alert-horizontal">
                        <x-moox-alert-content class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                class="w-6 h-6 stroke-current shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h3 class="font-bold">New message!</h3>
                                <div class="text-xs">You have 1 unread message</div>
                            </div>
                        </x-moox-alert-content>

                        <x-moox-alert-action class="flex justify-end w-full">
                            <button class="btn btn-sm">See</button>
                        </x-moox-alert-action>

                    </x-moox-alert>
                </div>
            </div>
        </div>



        <div>
            <p class="mb-3 text-2xl text-white">Modal</p>
            <div class="grid grid-cols-1 gap-5 p-5 bg-white rounded-lg">

                <div class="grid grid-cols-1 gap-5">

                    <label for="my_modal_8" class="btn btn-active btn-secondary">Open Checkbox Modal</label>


                    <x-moox-modal-checkbox id="my_modal_8">
                        <x-moox-modal-box>
                            <h3 class="text-lg font-bold">Hello Word</h3>
                            <p class="py-4">This modal works with anchor links</p>

                            <x-moox-modal-action>
                                <a href="#" class="btn">Yay!</a>
                            </x-moox-modal-action>
                        </x-moox-modal-box>
                    </x-moox-modal-checkbox>





                    <x-daisy-ui.button onclick="my_modal_2.showModal()" class="btn-active btn-primary">
                        Open Dialog Modal
                    </x-daisy-ui.button>

                    <x-moox-modal-dialog id="my_modal_2" class="modal-bottom sm:modal-middle">
                        <x-moox-modal-box>
                            <h3 class="text-lg font-bold">Mehod Dialog Modal</h3>
                            <p class="py-4">Press ESC key or click outside to close</p>

                            <x-moox-modal-action>
                                <form method="dialog">
                                    <!-- if there is a button, it will close the modal -->
                                    <button class="btn">Close</button>
                                </form>
                            </x-moox-modal-action>
                        </x-moox-modal-box>
                    </x-moox-modal-dialog>

                </div>
            </div>
        </div>



        <div>
            <p class="mb-3 text-2xl text-white">Breadcrumbs</p>
            <div class="grid grid-cols-1 gap-5 p-5 bg-white rounded-lg">

                <div class="grid grid-cols-1 gap-5">

                    <x-moox-breadcrum class="text-sm">
                        <ul>
                            <li><a>Home</a></li>
                            <li><a>Documents</a></li>
                            <li>Add Document</li>
                        </ul>
                    </x-moox-breadcrum>
                </div>
            </div>
        </div>


        <div>
            <p class="mb-3 text-2xl text-white">Menu</p>
            <div class="grid grid-cols-1 gap-5 p-5 bg-white rounded-lg">

                <div class="grid grid-cols-1 gap-5">

                    <x-moox-menu class="w-56 bg-base-200">
                        <x-moox-menu-item><a>Item 1</a></x-moox-menu-item>
                        <x-moox-menu-item><a class="menu-active">Item 2</a></x-moox-menu-item>
                        <x-moox-menu-item><a>Item 3</a></x-moox-menu-item>
                        <x-moox-menu-item class="menu-disabled"><a>Item 4</a></x-moox-menu-item>
                        <x-moox-menu-item><a>Item 5</a></x-moox-menu-item>
                    </x-moox-menu>


                    <x-moox-menu class="w-56 bg-base-200">
                        <x-moox-menu-item class="menu-title">Title</x-moox-menu-item>
                        <x-moox-menu-item><a class="menu-active">Item 1</a></x-moox-menu-item>
                        <x-moox-menu-item><a>Item 2</a></x-moox-menu-item>
                    </x-moox-menu>


                    <x-moox-menu class="menu-horizontal bg-base-200">
                        <x-moox-menu-item><a>Item 1</a></x-moox-menu-item>
                        <x-moox-menu-item><a class="menu-active">Item 2</a></x-moox-menu-item>
                        <x-moox-menu-item><a>Item 3</a></x-moox-menu-item>
                        <x-moox-menu-item class="menu-disabled"><a>Item 4</a></x-moox-menu-item>
                        <x-moox-menu-item><a>Item 5</a></x-moox-menu-item>
                    </x-moox-menu>


                    <x-moox-menu class="menu-horizontal bg-base-200">
                        <x-moox-menu-item>
                            <a class="tooltip" data-tip="Home">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                            </a>
                        </x-moox-menu-item>
                        <x-moox-menu-item>
                            <a class="tooltip" data-tip="Details">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </a>
                        </x-moox-menu-item>
                        <x-moox-menu-item>
                            <a class="tooltip" data-tip="Stats">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </a>
                        </x-moox-menu-item>
                    </x-moox-menu>

                </div>
            </div>
        </div>



        <div>
            <p class="mb-3 text-2xl text-white">Image</p>
            <div class="grid grid-cols-1 gap-5 p-5 bg-white rounded-lg">

                <div class="grid grid-cols-1 gap-5">

                    <x-moox-image-figure>
                        <x-moox-image path="https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp"
                            alt="Shoes" title="Shoes" class="w-full" />
                    </x-moox-image-figure>
                </div>
            </div>
        </div>



        <div>
            <p class="mb-3 text-2xl text-white">Cards</p>
            <div class="grid grid-cols-1 gap-5 p-5 bg-white rounded-lg">

                <div class="grid grid-cols-1 gap-5">

                    <x-moox-card class="shadow-sm card-side bg-base-100">

                        <x-moox-image-figure>
                            <x-moox-image path="https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp"
                                alt="Shoes" title="Shoes" class="w-96" />
                        </x-moox-image-figure>


                        <x-moox-card-body>
                            <x-moox-card-title>
                                New movie is released!
                            </x-moox-card-title>
                            <p>Click the button to watch on Jetflix app.</p>

                            <x-moox-card-action>
                                <button class="btn btn-primary">Watch</button>
                            </x-moox-card-action>
                        </x-moox-card-body>

                    </x-moox-card>



                    <x-moox-card class="shadow-sm bg-base-100 w-96">

                        <x-moox-image-figure>
                            <x-moox-image path="https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp"
                                alt="Shoes" title="Shoes" class="w-full" />
                        </x-moox-image-figure>


                        <x-moox-card-body>
                            <x-moox-card-title tag="h1">
                                Card Title
                                <div class="badge badge-secondary">NEW</div>
                            </x-moox-card-title>

                            <p>A card component has a figure, a body part, and inside body there are title and actions parts
                            </p>

                            <x-moox-card-action class="justify-end">
                                <div class="badge badge-outline">Fashion</div>
                                <div class="badge badge-outline">Products</div>
                            </x-moox-card-action>
                        </x-moox-card-body>
                    </x-moox-card>

                </div>
            </div>
        </div>


        <div>
            <p class="mb-3 text-2xl text-white">Collapse</p>
            <div class="grid grid-cols-1 gap-5 p-5 bg-white rounded-lg">

                <div class="grid grid-cols-1 gap-5">

                    <x-moox-collapse class="border collapse-plus bg-base-100 border-base-300">
                        <x-moox-collapse-title class="font-semibold">
                            <p>How do I create an account?</p>
                        </x-moox-collapse-title>
                        <x-moox-collapse-content class="text-sm">
                            <p>Click the "Sign Up" button in the top right corner and follow the registration process.</p>
                        </x-moox-collapse-content>
                    </x-moox-collapse>

                    <x-moox-collapse class="border collapse-arrow bg-base-100 border-base-300">
                        <x-moox-collapse-title class="font-semibold">
                            <p>How do I create an account?</p>
                        </x-moox-collapse-title>
                        <x-moox-collapse-content class="text-sm">
                            <p>Click the "Sign Up" button in the top right corner and follow the registration process.</p>
                        </x-moox-collapse-content>
                    </x-moox-collapse>

                    <x-moox-collapse>
                        <x-moox-collapse-title class="font-semibold">
                            <p>How do I create an account?</p>
                        </x-moox-collapse-title>
                        <x-moox-collapse-content class="text-sm">
                            <p>Click the "Sign Up" button in the top right corner and follow the registration process.</p>
                        </x-moox-collapse-content>
                    </x-moox-collapse>

                    <x-moox-collapse
                        class="bg-primary text-primary-content focus:bg-secondary focus:text-secondary-content collapse-open">
                        <x-moox-collapse-title class="font-semibold">
                            <p>How do I create an account?</p>
                        </x-moox-collapse-title>
                        <x-moox-collapse-content class="text-sm">
                            <p>Click the "Sign Up" button in the top right corner and follow the registration process.</p>
                        </x-moox-collapse-content>
                    </x-moox-collapse>

                </div>
            </div>
        </div>


        <div>
            <p class="mb-3 text-2xl text-white">Tabs</p>
            <div class="grid grid-cols-1 gap-5 p-5 bg-white rounded-lg">

                <div class="grid grid-cols-1 gap-5">

                    <x-moox-tab>
                        <a role="tab" class="tab">Tab 1</a>
                        <a role="tab" class="tab tab-active">Tab 2</a>
                        <a role="tab" class="tab">Tab 3</a>
                    </x-moox-tab>


                    <x-moox-tab class="tabs-box">
                        <a role="tab" class="tab">Tab 1</a>
                        <a role="tab" class="tab tab-active">Tab 2</a>
                        <a role="tab" class="tab">Tab 3</a>
                    </x-moox-tab>


                    <x-moox-tab class="tabs-lift">
                        <input type="radio" name="my_tabs_3" class="tab" aria-label="Tab 1" />
                        <x-moox-tab-content class="p-6 bg-base-100 border-base-300">
                            <p>Tab content 1</p>
                        </x-moox-tab-content>

                        <input type="radio" name="my_tabs_3" class="tab" aria-label="Tab 2" checked="checked" />
                        <x-moox-tab-content class="p-6 bg-base-100 border-base-300">
                            <p>Tab content 2</p>
                        </x-moox-tab-content>

                        <input type="radio" name="my_tabs_3" class="tab" aria-label="Tab 3" />
                        <x-moox-tab-content class="p-6 bg-base-100 border-base-300">
                            <p>Tab content 3</p>
                        </x-moox-tab-content>
                    </x-moox-tab>

                </div>
            </div>
        </div>


        {{-- 
        <div>
            <p class="mb-3 text-2xl text-white">example</p>
            <div class="grid grid-cols-1 gap-5 p-5 bg-white rounded-lg">

                <div class="grid grid-cols-1 gap-5">

                   
                </div>
            </div>
        </div> 
        --}}






    </div>
@endsection
