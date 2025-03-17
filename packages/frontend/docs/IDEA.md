# Moox Frontend Concept

Moox Frontend is a modular system designed to provide a frontend for Moox, using:

-   [Laravel 12](https://laravel.com/docs/11.x)
-   [Alpine JS](https://alpinejs.dev/)
-   [Alpine Ajax](https://alpine-ajax.js.org/)
-   [TailwindCSS 4](https://tailwindcss.com/)
-   [Daisy UI](https://daisyui.com/)
-   [Motion One](https://motion.dev/) (or GSAP, optional)

with inspiration from:

-   [Blade UI Kit](https://blade-ui-kit.com/)
-   [PenguinUI](https://www.penguinui.com/)
-   [Tailwind Plus](https://tailwindui.com/)
-   [Pines UI](https://devdojo.com/pines)
-   [Tailkit](https://tailkit.com/)
-   [Flowbite](https://flowbite.com/)
-   [Shadcn UI](https://ui.shadcn.com/)
-   [MUI](https://mui.com/)
-   [AlpineJS Components](https://alpinejs.dev/components)
-   [Alpine Toolbox](https://www.alpinetoolbox.com/)
-   [Envato Elements](https://elements.envato.com/web-templates/tailwind+css)
-   [Cruip](https://cruip.com/)

The content is managed through [Moox Entities](#moox-entities) (Items, Taxonomies, Modules) that define their own rendering in the Frontend.php class, for example:

-   [Moox Page](https://github.com/mooxphp/page)
-   [Moox Post](https://github.com/mooxphp/post)
-   [Moox Taxonomy](https://github.com/mooxphp/taxonomy)
-   [Moox SEO Module](https://github.com/mooxphp/seo)

[Navigation](#moox-navigation) is provided through:

-   [Moox Navigation](https://github.com/mooxphp/navigation)

[Media](#moox-media) is provided through:

-   [Moox Media](https://github.com/mooxphp/media)

[Localization](#moox-localization) for all Entities, Media and Navigation is provided through:

-   [Moox Localization](https://github.com/mooxphp/localization)

The [Frontend](#moox-frontend) is provided through:

-   [Moox Components](https://github.com/mooxphp/components)
-   [Moox Themes](https://github.com/mooxphp/theme-base)
-   [Moox Search](https://github.com/mooxphp/search)
-   [Moox Frontend](https://github.com/mooxphp/frontend)

## Moox Entities

Moox Entities are a core concept of Moox. Items (like Pages, Posts, Products), Taxonomies (like Categories, Tags), and Modules (like Forms, Comments) are all entities.

To render an entity in frontend, it only needs to have a Frontend.php class. Additional components added in the package also need to be defined in this class.

```php
class Frontend extends MooxFrontend
{
    protected string $layout = 'default';
    protected array $data = [];

    public function render()
    {
        return view('moox::frontend.entity', [
            'layout' => $this->layout,
            'data' => array_merge($this->data, [
                'meta' => $this->getMeta(),
                'content' => $this->getContent(),
                'related' => $this->getRelated()
            ])
        ]);
    }

    protected function getMeta(): array
    {
        return [
            'title' => $this->entity->title,
            'description' => $this->entity->description,
            'slug' => $this->entity->slug
        ];
    }

    protected function getContent(): array
    {
        return $this->entity->toArray();
    }

    protected function getRelated(): array
    {
        return [];
    }
}
```

There are some special packages that provide content:

### Moox SEO

Moox SEO is a module that extends page, post, product etc. But instead of just being a simple module, this package also adds other SEO features, like sitemap-generation, canonical URLs, OpenGraph, and Schema.org JSON-LD.

Example:

```php
class SeoFrontend extends Frontend
{
    protected function getMeta(): array
    {
        $meta = parent::getMeta();

        return array_merge($meta, [
            'canonical' => $this->getCanonicalUrl(),
            'openGraph' => $this->getOpenGraph(),
            'jsonLd' => $this->getJsonLd(),
            'meta' => $this->getMetaTags()
        ]);
    }

    protected function getCanonicalUrl(): string
    {
        return url($this->entity->slug);
    }

    protected function getOpenGraph(): array
    {
        return [
            'title' => $this->entity->seo_title ?? $this->entity->title,
            'description' => $this->entity->seo_description,
            'image' => $this->entity->featured_image?->getUrl()
        ];
    }

    protected function getJsonLd(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $this->entity->title,
            'description' => $this->entity->description
        ];
    }
}
```

### Moox Navigation

Moox Navigation is a package that provides a navigation system for Moox. It allows you to define and structure navigations, and provides Blade/Alpine components to render them.

```blade
<x-moox-navigation name="header" />
```

Moox Navigation should ship with a localized entity:

```sql
navigations
--------------
id
name
theme_id FK     # limit to theme (nullable)
frontend_id FK  # limit to frontend (nullable)
created_at
created_by
updated_at
updated_by
deleted_at
deleted_by

navigations_items
--------------
id
navigation_id
entity_id
entity_type
localization
created_at
created_by
updated_at
updated_by
deleted_at
deleted_by
```

### Moox Media

Moox Media is a package that provides a media library for Moox based on Spatie Media Library. It allows you to manage images, documents, and media files.

Like so?

```php

'featured' => $this->entity->getFirstMedia('featured'),
'gallery' => $this->entity->getMedia('gallery'),
'documents' => $this->entity->getMedia('documents')
'media' => $this->getMedia()

```

### Moox Localization

Moox Localization is a package that provides localization for Moox, based on Astrotomic Translatable. It allows you to manage translations for Moox entities.

Like so?

```php
class LocalizedFrontend extends Frontend
{
    protected string $locale;

    public function __construct()
    {
        $this->locale = app()->getLocale();
    }

    protected function getContent(): array
    {
        return $this->entity->translateOrDefault($this->locale)->toArray();
    }
}
```

## Moox Components

Moox ships with a robust **Blade + AlpineJS + Alpine Ajax + TailwindCSS + DaisyUI** component system, allowing developers to quickly build UI elements with powerful interactivity. The components are:

-   Fully customizable via TailwindCSS and DaisyUI.
-   Interactive and lightweight with AlpineJS.
-   Ajax-driven using Alpine Ajax.
-   Optimized for maintainability and theme inheritance.
-   Extendable by themes without modifying the core components.
-   Compatible with animations and scroll effects, configurable per theme.

### **Button Component**

A fully featured Blade component that supports multiple styles, loading states, and dynamic behaviors.

**Implementation:**

```php
namespace Moox\Components\Components\Buttons;

use Illuminate\View\Component;

class Button extends Component
{
    public string $type;
    public ?string $icon;
    public string $size;
    public bool $disabled;
    public bool $loading;
    public string $variant;
    public string $style;
    public ?string $animation;

    public function __construct(
        string $type = 'button',
        ?string $icon = null,
        string $size = 'md',
        bool $disabled = false,
        bool $loading = false,
        string $variant = 'primary',
        string $style = 'filled',
        ?string $animation = null
    ) {
        $this->type = $type;
        $this->icon = $icon;
        $this->size = $size;
        $this->disabled = $disabled;
        $this->loading = $loading;
        $this->variant = $variant;
        $this->style = $style;
        $this->animation = $animation;
    }

    public function render()
    {
        return view('components::components.buttons.button');
    }
}
```

```php
@php
    $classes = "btn inline-flex items-center justify-center font-medium transition-all duration-200";

    if ($style === 'filled') {
        $classes .= " btn-{$variant}";
    } elseif ($style === 'outline') {
        $classes .= " btn-outline btn-{$variant}";
    } elseif ($style === 'link') {
        $classes .= " btn-link text-{$variant}";
    }

    $sizes = [
        'sm' => 'btn-sm',
        'md' => 'btn-md',
        'lg' => 'btn-lg',
    ];
    $classes .= " {$sizes[$size] ?? 'btn-md'}";

    if ($disabled || $loading) {
        $classes .= " opacity-50 pointer-events-none";
    }

    $motionAttr = "";
    if ($animation) {
        $motionAttr = "data-animation='{$animation}'";
    }

@endphp

<button
    {{ $attributes->merge(['class' => $classes]) }}
    type="{{ $type }}"
    @if($disabled || $loading) disabled aria-disabled="true" @endif
    @if($loading) aria-busy="true" @endif
    aria-label="{{ $slot }}"
    {{ $motionAttr }}
>
    @if($loading)
        <span class="flex items-center gap-2">
            <span class="loading loading-spinner loading-xs"></span>
            Loading...
        </span>
    @else
        <span class="flex items-center gap-2">
            @if($icon) <x-moox-icon :name="$icon" /> @endif
            {{ $slot }}
        </span>
    @endif
</button>
```

**Usage:**

```php
// Default Button (Primary filled MD)
<x-moox-button>Click Me</x-moox-button>

// Primary outlined XL button with loading state
<x-moox-button style="outline" size="xl" loading>Click Me</x-moox-button>

// Secondary filled SM button with icon
<x-moox-button variant="secondary" size="sm" icon="arrow_forward">Click Me</x-moox-button>

// Link button with animation
<x-moox-button style="link" animation="fade">Click Me</x-moox-button>

// Disabled button
<x-moox-button disabled>Click Me</x-moox-button>

// Scroll animation
<div class="scroll-animation" data-animation="fade">Click Me</div>

// Motion One animation
<div class="motion-one-animation" data-animation="fade">Click Me</div>

// GSAP animation
<div class="gsap-animation" data-animation="fade">Click Me</div>


```

### Animation, Scroll Animation & Motion

Animations in Moox are optional and theme-controlled, allowing themes to integrate different animation libraries like AlpineJS Transitions, Motion One or GSAP.

This allows you to use the default animations without extra dependencies or to use the Moox Animation API to standarize animations across your project.

#### Default Animations

In Moox Components you can use AlpineJS x-transition without extra dependencies. This works out of the box:

```php
<div x-data="{ effect: 'bounce' }" :data-animation="effect">
    I animate dynamically!
</div>
```

or like this:

```php
<div x-data x-transition:enter="transition-opacity ease-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
    Animated Content
</div>
```

#### AlpineJS Scroll Plugin (x-intersect)

The AlpineJS Scroll Plugin (x-intersect) is a plugin that allows you to detect when an element is visible in the viewport, you can use it to trigger animations on scroll.

```php
<div x-data x-intersect="console.log('Element visible!')" class="p-10">
    Scroll to see me!
</div>
```

AlpineJS offers basic animation support without extra dependencies. An extremely lightweight solution, suitable for most use cases.

For advanced animations, themes can use the Animation API with 3rd party libraries like Motion One or GSAP.

#### Animation API

Moox Components offer a simple animation API:

| Attribute               | Purpose                         | Example                           |
| ----------------------- | ------------------------------- | --------------------------------- |
| animation               | on render (Ajax, Click, Scroll) | wobble-3 (3x), wobble (infinite)  |
| animation-in            | when element is added           | fade-in-600 (600 ms duration)     |
| animation-out           | when element is removed         | fade-out-500 (500 ms duration)    |
| scroll-animation-before | sets scroll trigger offset      | start end -10rem                  |
| motion                  | physics based animations        | spring-bounce, path-curve, custom |

#### with AlpineJS

With `animation`, `animation-in` and `animation-out` you can use AlpineJS animations like so:

Fade in for 600ms, fade out for 500ms, wobble forever:

```php
<x-moox-button animation-in="fade-in" animation-out="fade-out" animation="wobble">
    Animated Button
</x-moox-button>
```

Fade in for 400ms, fade out for 300ms, wobble once:

```php
<x-moox-button animation-in="fade-in-400" animation-out="fade-out-300" animation="wobble-1">
    Animated Button
</x-moox-button>
```

And scroll before the element is visible:

```php
<x-moox-button scroll-animation-before="start end -10rem" animation-in="fade-in-left-300">
    Animated Button
</x-moox-button>
```

#### Motion One

While Motion One uses the same API, it also supports physics based animations like path-curve and complex custom animations:

```php
<x-moox-button motion="spring-bounce">
    Animated Button
</x-moox-button>
```

That needs to be added to your theme's `motion.js` file:

```js
const animations = {
    "spring-bounce": {
        y: [-50, 0],
        opacity: [0, 1],
        transition: {
            type: "spring",
            stiffness: 400,
            damping: 10,
            mass: 1,
        },
    },
    // ...
};
```

Simplified example:

```php
@props([
'variant' => 'primary',
'size' => 'md',
'animation' => null, // Optional animation prop
])

<button
{{ $attributes->merge([
        'class' => "btn btn-$variant btn-$size",
        'data-animation' => $animation
    ]) }}
x-data="{ loading: false }"
@click="loading = true"

>

    <span x-show="!loading">{{ $slot }}</span>
    <span x-show="loading" class="hidden">Loading...</span>

</button>
```

## Moox Themes

Moox themes define the styling of components and layouts, using **DaisyUI** and **TailwindCSS** as the foundation. Each theme can:

-   Override default styles for Moox Components.
-   Define custom Tailwind configurations (`tailwind.config.js`).
-   Support animations using **AlpineJS, Motion One, or GSAP**.

### Theme Configuration

Themes can be configured in tailwind.config.js:

```js
export default {
    theme: {
        extend: {},
    },
    plugins: [require("daisyui")],
    daisyui: {
        themes: ["light", "dark", "cupcake"], // Themes Moox supports
    },
};
```

#### Fully Dynamic Theme Switching

Themes can be dynamically switched without changing components:

```php
<link rel="stylesheet" href="{{ asset('themes/' . config('moox.theme') . '/theme.css') }}">
```

#### Overriding Components in a Theme

If a theme wants to add features to components, it can override the component like this:

Theme Override: themes/my-theme/components/moox-button.blade.php

```
@props(['variant' => 'primary', 'size' => 'md'])

<button {{ $attributes->merge(['class' => "rounded-lg shadow px-6 py-3 text-lg bg-gradient-to-r from-blue-500 to-purple-600"]) }}>
    {{ $slot }}
</button>
```

### **Theme Inheritance**

Themes can extend a base theme by defining a parent in their configuration:

```php
return [
    'name' => 'Moox Featherlight',
    'extends' => 'moox-base-theme',
];
```

### **Component & Layout Inheritance**

Themes can extend a Blade component from the parent theme:

```blade
@extends(getThemeViewPath('layouts.master'))
```

or override only specific sections:

```blade
@extends(getThemeViewPath('layouts.master'))

@section('content')
    <h1>Custom Content from Featherlight</h1>
    @parent
@endsection
```

### Theme Assets

Theme assets are design-related files that are integral to the theme's UI and are not meant to be managed through the CMS:

```
themes/
  ├── theme-name/
  │   ├── src/                  # Source files for compilation
  │   │   ├── js/
  │   │   │   └── app.js
  │   │   └── css/
  │   │       └── app.css
  │   ├── dist/                 # Compiled assets with cache busting
  │   │   ├── js/
  │   │   │   └── app.[hash].js
  │   │   └── css/
  │   │       └── app.[hash].css
  │   └── public/              # Static theme assets
  │       └── images/
  │           ├── demo/        # Demo content, overridden by Media
  │           │   ├── logo.svg
  │           │   ├── favicon.svg
  │           │   └── hero.jpg
  │           ├── ui/          # UI design elements
  │           │   ├── pattern-bg.svg
  │           │   ├── divider-wave.svg
  │           │   └── loading-spinner.gif
  │           └── icons/       # Theme-specific icons
  │               └── custom-icons.svg
```

Theme assets can be managed through the database. Every Moox Themes provides an Entity that allows to assign Moox Media, text and colors to all available slots of a theme. We need to add localization to this entity, so we can have different assets for different locales.

```sql
themes
--------------
id
name
description
created_at
updated_at
```

Themes must register itself to the themes table and seed their settings to the theme_settings table, without a value.

```sql
theme_settings
--------------
id
theme_id      # Limit to Moox Theme (nullable)
slot_name     # Default slots are 'Logo', 'Favicon', 'Hero Image', 'Brand Name', 'Slogan', 'Primary Color'
type          # Defines the setting type: image, text, color
description   # Describes the expected file format, dimensions, hex, etc.
media_id      # Foreign key to Moox Media (nullable)
media_height  # Height of the image (nullable)
media_width   # Width of the image (nullable)
value         # Text, Alt, Title (nullable)
frontend_id   # limit to frontend (nullable)
localization  # limit to localization (nullable)
created_at
created_by
updated_at
updated_by
deleted_at
deleted_by
```

All content tables like pages, posts, products ship with their own seeders and factories.

Packages like Moox SEO, Moox Shop etc. should be able to add theme-settings, too.

## Moox Slug

Permalink Management and automatic slug generation implemented in all Moox Items and Taxonomies.

Like so?

```php
class SlugFrontend extends Frontend
{
    protected function generateSlug(): string
    {
        return str($this->entity->title)
            ->slug()
            ->append('-' . $this->entity->id)
            ->toString();
    }

    protected function getPermalink(): string
    {
        return url($this->entity->type . '/' . $this->entity->slug);
    }

    protected function validateSlug(string $slug): bool
    {
        return !$this->entity::where('slug', $slug)
            ->where('id', '!=', $this->entity->id)
            ->exists();
    }
}
```

## Moox Search

Implements Laravel Scout. Offers faceted search and filtering. Offers a search form and a search results page as well as a live search component powered by AlpineJS and Alpine Ajax.

## Moox Frontend

The core of the frontend system, integrating entities, themes, and routes. Ships with a Entity to manage frontends:

```sql
frontends
--------------
id
name
description
base_url       # Base URL for the frontend, e.g. https://example.com/frontend
static_cache   # Enable static cache (boolean)
created_at
updated_at
updated_by
deleted_at
deleted_by
```

By default, Moox Frontend will run without a frontend entry. Moox Navigation and Theme Settings will use the default frontend. When creating frontends, you can define different base URLs, using different navigations and theme settings.

Initially, Moox Frontend will use standard Laravel routes. Future optimizations may include cached permalinks (`Cache::put('moox_slugs', $array)`) for high-speed lookups, JSON-based caching for ultra-fast retrieval, or static route file generation for extreme performance.

Another potential solution is **static HTML generation**, allowing requests to be served entirely by the webserver or CDN, bypassing Laravel and eliminating database queries.
