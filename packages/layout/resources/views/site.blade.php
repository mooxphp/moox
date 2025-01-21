<!--
    This is should be a dynamic layout, that can be used by all resources.
    It should be able to render the defined header, navigations, footer, etc.
-->

<html>

<head>
    <title>My Website</title>
</head>

<body>

    @yield('header')
    @yield('navigation')
    @yield('content')
    @yield('footer')

</body>

</html>
