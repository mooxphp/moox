<ul {{ $attributes->merge(['class' => 'dropdown-content menu bg-base-100 rounded-box z-1 w-52 p-2 shadow-sm']) }}
    tabindex="0">
    {{ $slot }}
</ul>
