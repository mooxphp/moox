<!--
    This is the simple social for the frontend.
    It should just render a list of social icons.
    But the style and the social icons should be defined in the theme.
    The icons to be generated are defined in ... Moox Layout, I guess.
    I would do that in the DB, so create a new resource for that?

    Last notes for tomorrow:

    https://tailwindui.com/components/marketing/page-examples/landing-pages

    -   class and style should be defined in the theme and override the default ones
        this is the tricky part, because we need to have a way to define the class and style in the theme
        and then override the default ones without having to define the whole thing (layout, component, etc.)
        in every theme.
        -   so to be more clear: layout and component are the plain versions
        -   theme adds the class, style and other stuff
        -   like the social icons, other icons, and the tech like Alpine-Ajax, etc.
    -   we had a bunch of social icons, but we need new ones with X etc., Blade Icons?
    -   we need to create a new resource for the social icons
    -   we need to create a new resource for the menu items
    -   but both should be flexible, so we can create a bunch of them and use them in the theme
        -   e. g. heco-social-iconset, contains Facebook, X, Instagram, ...
        -   e. g. heco-article-menu, contains 7 products
        -   e. g. heco-footer-menu, contains 5 items
    -   components are a bit more complex ...
        -   base components are the ones that are tied to fields, so a field knows what components may fit
        -   composite components are sets of base components, like a card with a title, description, image, etc.
        -   they should also be renderless, able to be styled in the theme
-->

<div class="social">
    <ul>
        <li><a href="#"><i class="fab fa-facebook"></i></a></li>
    </ul>
</div>
