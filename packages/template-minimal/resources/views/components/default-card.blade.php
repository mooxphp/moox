@if ($item)
    <!--template minimal card-->
    <x-moox-card
        class="overflow-hidden bg-white rounded-md shadow-sm dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700">
        <a class="w-full md:flex" href="#">
            <figure class="w-full md:w-64">
                <img src="{{ $item['image'] ?? 'https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp' }}"
                    alt="Image" />
            </figure>
            <x-moox-card-body>
                <div class="pb-5">
                    <p class="mb-3 text-xs text-gray-500">{{ $item['datum'] ?? '' }}</p>
                    <x-moox-card-title class="text-primary-500">{{ $item['title'] ?? '' }}</x-moox-card-title>
                    <p>{{ $item['description'] ?? '' }}</p>
                </div>
            </x-moox-card-body>
        </a>
    </x-moox-card>
    <!--template minimal card end-->
@endif
