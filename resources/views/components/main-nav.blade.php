<nav class="flex justify-end gap-5">
    <x-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">
        Home
    </x-nav-link>
    <x-nav-link href="{{ route('packages') }}" :active="request()->routeIs('packages')">
        Packages
    </x-nav-link>
    <x-nav-link href="{{ route('license') }}" :active="request()->routeIs('license')">
        License
    </x-nav-link>
    <x-nav-link href="{{ route('docs') }}" :active="request()->routeIs('docs')">
        Docs
    </x-nav-link>
    <x-nav-link href="{{ route('support') }}" :active="request()->routeIs('support')">
        Support
    </x-nav-link>
    <a class="bg-transparent border border-pink-500 text-gray-200 px-2 py-1" href="{{ route('demo') }}">
        Demo
    </a>
    <a href="https://github.com/mooxphp/moox" class="flex items-center h-8 w-8">
        <img src="{{ asset('web/github.png') }}" alt="GitHub">
    </a>
</nav>
