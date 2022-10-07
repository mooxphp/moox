https://github.com/omarkhatibco/tailwind-css-variables

https://www.samdawson.dev/article/tailwind-theming

https://github.com/vendeka-nl/tailwind-config-php

https://github.com/SimeonGriggs/tailwind-css-palette-generator


## Vite

Vite needs to run a build of a theme, instead of building a single app. But this may not be done inside the package. Instead, we need
the possibility to build multiple configurations like this:

- Admin
    - admin.css
    - admin.js
- Website
    - website.css
    - website.js
- Prio App
    - prioapp.css
    ...

Like TYPO3's page root setting we need to define the entry point for those apps. The TYPO3 page tree is a unbelievable flexible thing, where you can mount a domain, define root TS in any level. But is this a good idea?

The best idea for now is to create a route group middleware for this:

- Route Group   admin.tallui.io   admin-theme
- Route Group   ...

But how will it work?

While developing a theme:

Changing files in
        /current_package/views
        /another_package/views - many of them, components, admin ...
        /app/views - possibly not

The theme contains PostCSS, Tailwind and Vite config and registers the assets with the vite-directive, so all features like hot-reloading will work. The build is in the package.


While using the CMS later:

The theme will deliver a complete CSS file, means every styled aspect of the theme is build into the CSS/JS that will be included by the theme, if installed and called by the route middleware.

Using the theme as is (changing things only by css root variables, maybe), means there is no need to rebuild.

Changing or adding CSS/JS means we need to rebuild. Two ways

- As long there's no graphical interfaces, it is the developers part to do such changes and 'npm run build'
- When there's is a GUI (Designer) for that, we need to rebuild the assets in the package

Oh, see:

- https://github.com/qirolab/laravel-themer
- https://github.com/igaster/laravel-theme

Both with middleware.

## What is a theme?

A Laravel package? containing

- Configuration
    Laravel? config
        - Theme inheritance
    Vite config
    PostCSS config
    Tailwind config
- Assets, optional
- Components, optional
- Views, optional
- Layouts (Page layouts)

