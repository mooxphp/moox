# TALLUI AdminPanel W-I-P

The pure HTML-version of our AdminPanel. I am not sure, if this version will evolve.

## Alternatives and Inspiration

There are a lot of Admin Panels, CRUD Generators and UI Kits out there. Our selection mostly depends on the TALL-Stack.

- [TALL Frontent Preset](https://github.com/laravel-frontend-presets/tall) - Official UI preset.
- [Laravel Breeze](https://laravel.com/docs/9.x/starter-kits) - Official Starter Kit with Auth.
- [Laravel Jetstream](https://jetstream.laravel.com/) - Official Starter Kit with Auth, Register, 2-FA
- [Blade UIkit](https://blade-ui-kit.com/) – Components for the TALL-Stack, maintained from the Laravel devs
- [WireUI](https://livewire-wireui.com/) - Components with Livewire.
- [Filament](https://filamentphp.com/) - TALL stack UI und CRUD Generator.
- [Quick Admin Panel](https://quickadminpanel.com/) - Admin und CRUD Generator, TALL-Version verfügbar, $100/year.
- [Laravel Nova](https://nova.laravel.com/) - Offizielle Lösung mit Vue.js, kostet einmalig $199
- [Voyager](https://voyager.devdojo.com/) - Admin + CRUD Generator von Devdojo
- [Craftable](https://getcraftable.com/) - Freemium CRUD Generator and Admin.
- [Lean Admin](https://lean-admin.dev/) - TALL-stack basierendes Adminpanel, coming soon, nicht kostenfrei, [sneak peek](https://laravel-news.com/lean-admin-sneak-peek).

Siehe auch [Awesome Tall Stack](https://github.com/livewire/awesome-tall-stack) (Livewire Datatables, Views etc.)

- https://github.com/moesaid/cleopatra
- https://github.com/abhaytalreja/taildash
- https://technext.github.io/k-wd-dashboard/



Icons used

All icons used should be in a config file that allows to alias icons. With an alias it is possible to change iconsets.

Default to ... https://blade-ui-kit.com/blade-icons?set=66 or https://fontawesome.com/ or https://fonts.google.com/icons

https://materialdesignicons.com/





Why not DaisyUI?

- Missing colors and accent colors, see https://m3.material.io/styles/color/the-color-system/key-colors-tones, can be added like https://github.com/saadeghi/daisyui/discussions/653 but are missing in components
- Some mistakes in design like the button click animation, all uppercase in buttons, the full colored hover for outline buttons and more



Solution?

Create your own UI kit ... start with colors, use utility classes and components from daisyui, but be smarter

button bg-primary btn-pop -> extract to utilities

- https://daisyui.com/components/button/
- https://tailwindcss.com/docs/plugins
- https://daily.dev/blog/creating-a-custom-tailwind-css-color-plugin
- https://www.youtube.com/watch?v=M7Ao-VeL-h4
- https://github.com/vigetlabs/tailwindcss-plugins

Create components from

- https://github.com/saadeghi/daisyui/blob/master/src/components/styled/button.css
- https://github.com/saadeghi/daisyui/blob/master/src/components/unstyled/button.css
- https://github.com/saadeghi/daisyui/blob/master/src/utilities/styled/button.css
- https://github.com/saadeghi/daisyui/blob/master/src/utilities/unstyled/button.css

See also https://flowbite.com/docs/getting-started/introduction/



Extract Tailpine Components

- Password Toggle - https://tailwindcomponents.com/component/login-9
- Dark Mode Switch - https://tailwindcomponents.com/component/admin-dashboard-along-with-dark-mode-responsive-sidebar-7



Use good fonts

- https://fonts.google.com/noto/specimen/Noto+Sans
- https://fonts.google.com/noto/specimen/Noto+Serif
- https://fonts.google.com/noto/specimen/Noto+Sans+Mono
- https://fonts.google.com/specimen/Caveat?category=Handwriting fallback to https://fonts.google.com/noto/specimen/Noto+Serif
- https://fonts.google.com/specimen/Baloo+2?category=Display fallback to https://fonts.google.com/noto/specimen/Noto+Sans+Display



Colors

- Primary, or brand color
  - primary - 
  - on-primary
  - container-primary
  - on-container-primary
  - dark-primary
  - on-dark-primary
  - dark-container-primary
  - on-dark-container-primary
- Secondary, another brand color or color that fits primary
  - secondary
  - on-secondary
  - container-secondary
  - on-container-secondary
- Tertiary, optional third color
  - tertiary 
  - on-primary
  - container-primary
  - on-container-primary
- Background, background and text
  - background
  - on-background
  - container-background
  - on-container-background
- Surface, tables and cards
  - surface
  - on-surface
  - container-surface
  - on-container-surface
- Callout, or call-to-action
  - callout
  - on-callout
  - container-callout
  - on-container-callout
- Success, should be kind of green
  - success
  - on-success
  - container-success
  - on-container-success
- Info, should be kind of blue
  - info
  - on-info
  - container-info
  - on-container-info
- Warning, should be yellow to orange
  - warning
  - on-warning
  - container-warning
  - on-container-warning
- Error, should be kind of red
  - error
  - on-error
  - container-error
  - on-container-error
