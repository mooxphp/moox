@extends('layouts.app')


@section('content')
    <div class="container grid grid-cols-1 px-5 mx-auto bg-[#F0F0F0] gap-14 py-14 ">


        <div class="flex justify-center ">
            <div class="bg-white divide-x divide-gray-200 rounded-lg">
                <x-moox-dropdown class="dropdown-hover">
                    <div tabindex="0" role="button" class="px-3 py-2">Schweißfittings</div>
                    <x-moox-dropdown-liste tabindex="0" class="p-0! divide-y divide-gray-200">
                        <li class="px-3 py-2 hover:bg-gray-100">Rohrbogen</li>
                        <li class="px-3 py-2 hover:bg-gray-100">T- X- Y- Stücke</li>
                        <li class="px-3 py-2 hover:bg-gray-100">T-/ Hosen- Bogen</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Reduzierungen</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Kappen und Böden</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Bördel</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Sattelstutzen</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Orbitale</li>
                        <li class="px-3 py-2 hover:bg-gray-100">ANSI / ASME</li>
                    </x-moox-dropdown-liste>
                </x-moox-dropdown>
                <x-moox-dropdown class="dropdown-hover">
                    <div tabindex="0" role="button" class="px-3 py-2">Gewindefittings</div>
                    <x-moox-dropdown-liste tabindex="0" class="p-0! divide-y divide-gray-200">
                        <li class="px-3 py-2 hover:bg-gray-100">Fittings</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Verschraubungen</li>
                    </x-moox-dropdown-liste>
                </x-moox-dropdown>
                <x-moox-dropdown class="dropdown-hover">
                    <div tabindex="0" role="button" class="px-3 py-2">Industriearmaturen</div>
                    <x-moox-dropdown-liste tabindex="0" class="p-0! divide-y divide-gray-200">
                        <li class="px-3 py-2 hover:bg-gray-100">Kugelhähne</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Stellantriebe</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Ablasshähne</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Absperrschieber</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Absperrventile</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Rückschlag-...</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Schmutzfänger</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Mess- u. Regeltechnik</li>
                    </x-moox-dropdown-liste>
                </x-moox-dropdown>
                <x-moox-dropdown class="dropdown-hover">
                    <div tabindex="0" role="button" class="px-3 py-2">Flansche</div>
                    <x-moox-dropdown-liste tabindex="0" class="p-0! divide-y divide-gray-200">
                        <li class="px-3 py-2 hover:bg-gray-100">Vorschweißflansche</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Gewindeflansche</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Blindflansche</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Glatte Flansche</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Lose Flansche</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Bördel und Bunde</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Flansch-Formstücke</li>
                        <li class="px-3 py-2 hover:bg-gray-100">ANSI/ ASME</li>
                        <li class="px-3 py-2 hover:bg-gray-100">sonstiges</li>
                    </x-moox-dropdown-liste>
                </x-moox-dropdown>
                <x-moox-dropdown class="dropdown-hover">
                    <div tabindex="0" role="button" class="px-3 py-2">Systeme</div>
                    <x-moox-dropdown-liste tabindex="0" class="p-0! divide-y divide-gray-200">
                        <li class="px-3 py-2 hover:bg-gray-100">TEEKAY</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Victaulic®</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Pressfittings</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Schnellkupplungen</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Klemmverbindungen</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Schneidringverschr.</li>
                        <li class="px-3 py-2 hover:bg-gray-100">DIN 11864/ DIN 11853</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Geländerbau</li>
                    </x-moox-dropdown-liste>
                </x-moox-dropdown>
                <x-moox-dropdown class="dropdown-hover">
                    <div tabindex="0" role="button" class="px-3 py-2">Getränkearmaturen</div>
                    <x-moox-dropdown-liste tabindex="0" class="p-0! divide-y divide-gray-200">
                        <li class="px-3 py-2 hover:bg-gray-100">Rohrverschraubungen</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Rohrformstücke</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Schellen/ Bügel</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Tri-Clamp</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Ventile & Hähne</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Flanschverbindungen</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Schaugläser</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Filter & Siebe</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Sprühköpfe</li>
                    </x-moox-dropdown-liste>
                </x-moox-dropdown>
                <x-moox-dropdown class="dropdown-hover">
                    <div tabindex="0" role="button" class="px-3 py-2">Montagematerial</div>
                    <x-moox-dropdown-liste tabindex="0" class="p-0! divide-y divide-gray-200">
                        <li class="px-3 py-2 hover:bg-gray-100">Rohrschellen</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Rundstahlbügel</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Schlauchschellen</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Rohre</li>
                        <li class="px-3 py-2 hover:bg-gray-100">Stabstähle</li>
                    </x-moox-dropdown-liste>
                </x-moox-dropdown>
            </div>
        </div>

        <div class="mx-auto ">
            <div class="m-3">
                <x-moox-breadcrum class="text-sm">
                    <ul>
                        <li><a>
                                <x-gmdi-home class="w-6 h-6 text-blue-500" />
                            </a>
                        </li>
                        <li>Schweißfittings</li>
                    </ul>
                </x-moox-breadcrum>
            </div>

            <div class="flex gap-7">
                <section class="w-[183px]">
                    <div class="p-2 mb-2 text-center text-[#616161] bg-[#CCC] rounded-lg">
                        <p class="text-sm font-bold">
                            aktuelle Auswahl
                        </p>
                    </div>

                    <x-moox-menu class="w-full bg-base-200">
                        <x-moox-menu-item><a class="menu-active">Schweißfittings</a></x-moox-menu-item>
                        <x-moox-menu-item><a>Gewindefittings</a></x-moox-menu-item>
                        <x-moox-menu-item><a>Industriearmaturen</a></x-moox-menu-item>
                        <x-moox-menu-item><a>Flansche</a></x-moox-menu-item>
                        <x-moox-menu-item><a>Systeme</a></x-moox-menu-item>
                        <x-moox-menu-item><a>Getränkearmaturen</a></x-moox-menu-item>
                        <x-moox-menu-item><a>Montagematerial</a></x-moox-menu-item>
                    </x-moox-menu>

                </section>

                <section class="w-[505px] ">
                    <div class="px-5 py-2 mb-2 text-[#616161] bg-[#CCC] rounded-lg shadow-md">
                        <p class="text-sm font-bold">
                            Edelstahl Schweißfittings
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-2">

                        <x-moox-card class="shadow-sm card-side bg-base-100">
                            <x-moox-image-figure class="w-[100px] m-3">
                                <x-moox-image
                                    path="https://www.heco.de/webservice/pictures/articlegroup/thumbnail/320863.png?size=100"
                                    alt="" title="" class="w-full h-24 " />
                            </x-moox-image-figure>

                            <x-moox-card-body>
                                <x-moox-card-title class="text-xl text-[#616161]">Rohrbogen</x-moox-card-title>

                                <ul class="flex items-center gap-3">
                                    <li><a href="" class="text-[#005d9d] text-nowrap text-sm">geschweißt</a></li>
                                    <li><a href="" class="text-[#005d9d] text-nowrap text-sm">nahtlos</a></li>
                                </ul>

                            </x-moox-card-body>
                        </x-moox-card>



                        <x-moox-card class="shadow-sm card-side bg-base-100">

                            <x-moox-image-figure class="w-[100px] m-3">
                                <x-moox-image
                                    path="https://www.heco.de/webservice/pictures/articlegroup/thumbnail/321543.png?size=100"
                                    alt="" title="" class="w-full h-24 " />
                            </x-moox-image-figure>


                            <x-moox-card-body>
                                <x-moox-card-title class="text-xl text-[#616161]">T- X- Y- Stücke</x-moox-card-title>

                                <ul class="flex flex-wrap items-center gap-3">
                                    <li><a href="" class="text-[#005d9d] text-nowrap text-sm">geschweißt</a></li>
                                    <li><a href="" class="text-[#005d9d] text-nowrap text-sm">geschweißt, red.
                                            Abgang</a>
                                    </li>
                                    <li><a href="" class="text-[#005d9d] text-nowrap text-sm">nahtlos</a></li>
                                    <li><a href="" class="text-[#005d9d] text-nowrap text-sm">nahtlos, red.
                                            Abgang</a></li>
                                </ul>

                            </x-moox-card-body>
                        </x-moox-card>


                        <x-moox-card class="shadow-sm card-side bg-base-100">

                            <x-moox-image-figure class="w-[100px] m-3">
                                <x-moox-image
                                    path="https://www.heco.de/webservice/pictures/articlegroup/thumbnail/1400831.png?size=100"
                                    alt="" title="" class="w-full h-24 " />
                            </x-moox-image-figure>


                            <x-moox-card-body>
                                <x-moox-card-title class="text-xl text-[#616161]">T-/ Hosen- Bogen</x-moox-card-title>

                                <ul class="flex flex-wrap items-center gap-3">
                                    <li><a href="" class="text-[#005d9d] text-nowrap text-sm">Hosen-Bogen</a></li>
                                    <li><a href="" class="text-[#005d9d] text-nowrap text-sm">T-Bogen</a>
                                    </li>
                                    <li><a href="" class="text-[#005d9d] text-nowrap text-sm">Red.-T-Bogen</a></li>
                                    <li><a href="" class="text-[#005d9d] text-nowrap text-sm">Einschweißbogen</a>
                                    </li>
                                    <li><a href="" class="text-[#005d9d] text-nowrap text-sm">T-Stück 45°</a></li>
                                    <li><a href="" class="text-[#005d9d] text-nowrap text-sm">Mehr...</a></li>
                                </ul>

                            </x-moox-card-body>
                        </x-moox-card>



                        <x-moox-card class="shadow-sm bg-base-100">

                            <x-moox-image-figure class="m-3 overflow-hidden rounded-lg">
                                <x-moox-image
                                    path="https://www.heco.de/cms/fileadmin/heco/Produktbilder/SF_Gruppe_grau.jpg"
                                    alt="" title="" class="w-full" />
                            </x-moox-image-figure>


                            <x-moox-card-body>
                                <x-moox-card-title class="text-xl text-[#616161]">Schweißfittings</x-moox-card-title>

                                <p>Formteile zum Schweißen aus Edelstahl</p>

                            </x-moox-card-body>
                        </x-moox-card>

                    </div>
                </section>


                <section class="w-[221px]">
                    <div class="px-5 py-2 mb-2 text-[#616161] bg-[#CCC] rounded-lg shadow-md">
                        <p class="text-sm font-bold">
                            verwandte Artikel
                        </p>
                    </div>

                    <x-moox-card class="shadow-sm card-side bg-base-100">

                        <x-moox-image-figure class="w-[60px] m-2">
                            <x-moox-image
                                path="https://www.heco.de/webservice/pictures/articlegroup/thumbnail/1400831.png?size=100"
                                alt="" title="" class="w-full h-[60px]" />
                        </x-moox-image-figure>


                        <x-moox-card-body class="flex-row items-center p-3">

                            <a href="" class="text-xs">Rohrbogen 90° Typ 3D</a>

                        </x-moox-card-body>
                    </x-moox-card>

                </section>
            </div>
        </div>


    </div>
@endsection
