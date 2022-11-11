<!DOCTYPE html>
<html>
    <head>
        <title>TallUI Workspace</title>
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
        @yield('content')
    </body>
</html>
