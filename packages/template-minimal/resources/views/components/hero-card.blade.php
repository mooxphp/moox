<div>
     <!--hero card -->
     <x-moox-card class="overflow-hidden bg-green-500 rounded-md shadow-sm">
        <a class="relative w-full" href="#">
            <figure class="w-full">
                <img src="{{ $item['image'] ?? 'https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp' }}"
                    alt="Image" class="object-cover w-full h-full" />
            </figure>
            <x-moox-card-body
                class="bottom-0 left-0 right-0 z-10 bg-white md:absolute dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700">
                <div class="pb-5">
                    <p class="mb-3 text-xs text-gray-500">12. November 2025</p>
                    <x-moox-card-title class="text-primary-500">
                        Fake Title 1
                    </x-moox-card-title>
                    <p>Dies ist eine gefälschte Beschreibung für Artikel 1.</p>
                </div>
            </x-moox-card-body>
        </a>
    </x-moox-card>
     <!--hero card end-->
</div>