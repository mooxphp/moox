<div>
    <h1 class="text-2xl font-bold mb-5">Template Pro</h1>

    <div x-data="{}" class="grid grid-cols-3 gap-5">
        @foreach($data as $item)
            <x-moox-card
                class="overflow-hidden bg-white rounded-md shadow-sm dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700">
                <a class="w-full " href="#">
                    <figure class="w-full">
                        <img src="{{ $item['image'] ?? 'https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp' }}"
                            alt="Image" />
                    </figure>
                    <x-moox-card-body>
                        <div class="pb-5">
                            <p class="mb-3 text-xs text-gray-500">{{ $item['datum'] ?? '' }}</p>
                            <x-moox-card-title class="text-[#4169e1]">{{ $item['title'] ?? '' }}</x-moox-card-title>
                            <p>{{ $item['description'] ?? '' }}</p>
                        </div>
                    </x-moox-card-body>
                </a>
            </x-moox-card>
        @endforeach
    </div>

    <div class="flex justify-center mt-10">
        <a href="#" class="btn btn-primary">Mehr News</a>
    </div>
</div>
