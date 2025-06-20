@php
$classes = 'inline-flex items-center justify-center rounded-md font-medium transition-colors';

if ($style == 'filled') { $classes .= ' bg-gray-900 text-white hover:bg-gray-700'; }
elseif ($style == 'outline') { $classes .= ' border border-gray-300 text-gray-700 hover:bg-gray-50'; }
elseif ($style == 'link') { $classes .= ' text-gray-700 hover:text-gray-900'; }

if ($variant == 'primary') $classes .= ' bg-primary-600 text-white hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2';
elseif ($variant == 'secondary') $classes .= ' bg-secondary-600 text-white hover:bg-secondary-700 focus:ring-2 focus:ring-secondary-500 focus:ring-offset-2';
elseif ($variant == 'light') $classes .= ' bg-gray-200 text-gray-700 hover:bg-gray-300 focus:ring-2 focus:ring-gray-400 focus:ring-offset-2';
elseif ($variant == 'dark') $classes .= ' bg-gray-800 text-white hover:bg-gray-900 focus:ring-2 focus:ring-gray-700 focus:ring-offset-2';

if ($disabled || $loading) $classes .= ' opacity-50 cursor-not-allowed';


@endphp

<button
    {{ $attributes->merge(['class' => $classes]) }}
    type="{{ $type }}"
    @if($disabled || $loading) disabled @endif
>
    @if($loading)
        <span class="flex items-center gap-2">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading...
        </span>
    @else
        <span class="flex items-center gap-2">
            @if($icon) <x-moox-icon :name="$icon" /> @endif
            {{ $slot }}
        </span>
    @endif
</button>