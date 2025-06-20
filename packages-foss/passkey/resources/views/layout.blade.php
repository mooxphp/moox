<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Passkey</title>

    <link href="https://fonts.bunny.net/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">


    <link rel="shortcut icon" href="/favicon.svg" type="image/svg+xml">

    <meta property="og:image" content="{{ url('/social-demo-site.png') }}" />
    <meta property="og:image:width" content="1146" />
    <meta property="og:image:height" content="600" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url('/') }}" />
    <meta property="og:title" content="Laravel Passkey Authentication Demo | Joe Codes" />
    <meta property="og:description"
        content="This is a demo of how to authenticate users in your Laravel app using a passkey." />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:creator" content="@joetannenbaum" />

    <script src="https://unpkg.com/@simplewebauthn/browser/dist/bundle/index.umd.min.js"></script>
    <script src="{{ asset('vendor/passkey/passkey.js') }}"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])


</head>

<body class="h-full">
    <div class="relative flex flex-col justify-center min-h-full px-4 py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-auto h-12 mx-auto">
                <defs></defs>
                <circle cx="12" cy="12" r="9" fill="#9feaff"></circle>
                <circle cx="12" cy="12" r="11.5" fill="none" stroke="#00303e" stroke-linecap="round"
                    stroke-linejoin="round">
                </circle>
                <path d="M18,11A6,6,0,0,0,6,11v5.5l6,3,6-3Z" fill="#dff9ff"></path>
                <path d="M12,5a6,6,0,0,0-6,6v5.5l6,3Z" fill="#ffffff"></path>
                <line x1="6" y1="13" x2="6" y2="13.667" fill="none" stroke="#00303e" stroke-linecap="round"
                    stroke-linejoin="round">
                </line>
                <path d="M15.317,6A6,6,0,0,0,6,11" fill="none" stroke="#00303e" stroke-linecap="round"
                    stroke-linejoin="round">
                </path>
                <path d="M18,12.167V11a5.972,5.972,0,0,0-1.01-3.333" fill="none" stroke="#00303e" stroke-linecap="round"
                    stroke-linejoin="round"></path>
                <line x1="18" y1="16" x2="18" y2="14.167" fill="none" stroke="#00303e" stroke-linecap="round"
                    stroke-linejoin="round"></line>
                <path d="M16,13.167V11a4,4,0,0,0-8,0v6" fill="none" stroke="#00303e" stroke-linecap="round"
                    stroke-linejoin="round">
                </path>
                <line x1="16" y1="17" x2="16" y2="15.167" fill="none" stroke="#00303e" stroke-linecap="round"
                    stroke-linejoin="round"></line>
                <line x1="10" y1="14.667" x2="10" y2="18" fill="none" stroke="#00303e" stroke-linecap="round"
                    stroke-linejoin="round"></line>
                <path d="M14,18V11a2,2,0,0,0-4,0v1.667" fill="none" stroke="#00303e" stroke-linecap="round"
                    stroke-linejoin="round">
                </path>
                <line x1="12" y1="11.333" x2="12" y2="19" fill="none" stroke="#00303e" stroke-linecap="round"
                    stroke-linejoin="round"></line>
                <line x1="6" y1="15.5" x2="6" y2="16" fill="none" stroke="#00303e" stroke-linecap="round"
                    stroke-linejoin="round">
                </line>
            </svg>
            {{-- <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-auto h-12 mx-auto">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M7.864 4.243A7.5 7.5 0 0119.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 004.5 10.5a7.464 7.464 0 01-1.15 3.993m1.989 3.559A11.209 11.209 0 008.25 10.5a3.75 3.75 0 117.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 01-3.6 9.75m6.633-4.596a18.666 18.666 0 01-2.485 5.33" />
            </svg> --}}
            <h2 class="mt-6 text-3xl font-bold tracking-tight text-center text-gray-900">Laravel Passkeys Demo</h2>
            <p class="mt-2 text-sm text-center text-gray-600">
                This is a demo of how to authenticate users in your Laravel app using a passkey. This data is deleted
                every 30 minutes.
            </p>
            <p class="mt-2 text-sm text-center text-gray-600">
                <a href="https://blog.joe.codes/implementing-passkey-authentication-in-your-laravel-app"
                    class="underline text-sky-600" target="_blank">Read the blog post</a> | <a
                    href="https://github.com/joetannenbaum/laravel-passkeys-demo" class="underline text-sky-600"
                    target="_blank">View the code</a>
            </p>
        </div>

        @yield('content')

        <div class="absolute bottom-0 left-0 right-0 pb-4 mt-auto text-sm text-center text-gray-500">
            <p>Launched with <a class="underline" target="_blank"
                    href="https://bellows.dev/?utm_source=laravelpasskeysdemo">Bellows</a></p>
        </div>
    </div>
</body>

</html>
