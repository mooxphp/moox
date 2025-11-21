<div>
    <h1 class="mb-5 text-2xl font-bold">Template Minimal</h1>

    <div class="mb-10">
        <x-moox-hero-card />
    </div>

    <div x-data="{}" class="grid grid-cols-1 gap-5">
        @foreach ($data as $item)
            <x-moox-default-card :item="$item" />
        @endforeach
    </div>

    <div>
        <h2 class="text-2xl font-bold mb-10">News</h2>

        <div x-data="{}" class="grid grid-cols-1 gap-5">
            <div class="flex gap-7">
                <div>
                    <p>November 17, 2025 <br />erstellt von: John Doe</p>
                    
                </div>
                <div>
                    <h3 class="text-lg font-bold"><a href="">blog post Title</a></h3>
                    <p>News Description</p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-center mt-10">
        <a href="#" class="text-white btn bg-primary-500 hover:bg-primary-600">Mehr News</a>
    </div>
</div>
