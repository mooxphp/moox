<form method="POST" @isset($action) action="{{ $action }}" @endisset>
    @csrf
    @method($method)

    <button type="submit"
        {{ $attributes->merge([
            'class' => '

                        inline-flex
                        items-center
                        justify-center
                        px-4
                        py-2
                        text-sm
                        font-medium
                        border
                        border-transparent
                        rounded-md
                        shadow-sm
                        focus:outline-none
                        focus:ring-2
                        focus:ring-offset-2
                        sm:w-auto

                        focus:outline-red-500
                        focus:border-red-500
                        focus:ring-offset-transparent

                        focus:ring-red-500
                        text-white
                        bg-sky-800
                        hover:bg-sky-600',
        ]) }}>

        {{ $slot }}
    </button>
</form>
