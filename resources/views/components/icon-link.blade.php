<a
    href="{{ route('blade-icon', $icon) }}"
    class="flex flex-col items-center justify-between w-full h-full p-3 transition duration-300 ease-in-out border border-gray-200 text-gray-500 rounded-lg hover:text-scarlet-500 hover:shadow-md"
>
    {{ svg($icon->name, 'w-8 h-8') }}

    <span class="text-center truncate max-w-full mt-3">
        {{ $icon->name }}
    </span>
</a>
