<!--
    This is the simple header for the frontend.
    It should have a logo, a search field, and a menu.
-->

<div class="header">
    <div class="logo">
        <img
            src="{{ asset('images/logo.png') }}"
            alt="Logo"
        >
    </div>

    <div class="search">
        <input
            type="text"
            placeholder="Search"
        >
    </div>

    <div class="menu">
        <ul>
            <li><a href="#">Home</a></li>
        </ul>
    </div>
</div>
