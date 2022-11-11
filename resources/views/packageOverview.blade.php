<!DOCTYPE html>
<html>
    <head>
        <title>TallUI is coming soon</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="png" href="{{ asset('build/assets/images/icons/tallui-logo.svg') }}">


        <!-- Fonts -->
        <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        <style>

        </style>

        <style>
            body {
                font-family: 'Nunito', sans-serif;
            }
        </style>
    </head>
    <body class="bg-[#001829] bg-[url('../../public//build/assets/images/bg.jpg')] bg-no-repeat bg-right-top text-[#0e9adc]">
        <head>
            <nav class="flex justify-between m-8">
                <img  src="/build/assets/images/logo.png">
                <div class="flex items-center text-3xl">
                    <a class="px-4 font-normal" href="/">Home</a>
                    <a class="px-4 font-normal" href="/packages">Packages</a>
                    <a href="https://github.com/usetall/tallui">
                        <img class="px-4 " src="{{ asset('build/assets/images/icons/git.svg') }} " alt="git">

                    </a>
                </div>
            </nav>
        </head>
       <div class="flex">
        <ul>
            <li class="px-12 text-xl"><a href="/package/app">App</a> </li>
            <li class="px-12 text-xl"><a href="/package/form">Form</a> </li>
        </ul>
        <div class="text-white">
        </div>
       </div>
<div>
    <x-directory-digger></x-directory-digger>
</div>
    </body>
</html>
