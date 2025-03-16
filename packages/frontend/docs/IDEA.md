# Moox Frontend Concept

Moox Frontend is a modular system designed to provide a frontend for existing Moox packages like Media, Localization, Post, Page, and Taxonomy. It enables seamless content management by extending entities and integrating configurable themes. Moox Core remains backend-focused, while the frontend is handled separately through the following packages:

## Tooling

-   [Laravel 12](https://laravel.com/docs/11.x)
-   [Alpine JS](https://alpinejs.dev/)
-   [Alpine Ajax](https://alpine-ajax.js.org/)
-   [TailwindCSS 4](https://tailwindcss.com/)
-   [Daisy UI](https://daisyui.com/)

## Inspiration for components

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

## Inspiration for themes

-   [Envato Elements](https://elements.envato.com/web-templates/tailwind+css)
-   [Cruip](https://cruip.com/)

## Moox Components

Moox ships with a robust Blade + AlpineJS + Alpine Ajax + TailwindCSS component system, allowing developers to quickly build UI elements with powerful interactivity. The components are:

-   Fully customizable via TailwindCSS.
-   Interactive and lightweight with AlpineJS.
-   Ajax-driven using Alpine Ajax.
-   Optimized for maintainability and theme inheritance.

### Button Component

A fully featured Blade component that supports multiple styles, loading states, and dynamic behaviors.

**Usage:**

```php
<x-moox-button variant="solid" size="lg" icon="arrow_forward" x-data="{ loading: false }" @click="loading = true">
    Click Me
</x-moox-button>
```

**Implementation:**

```php
<button
    {{ $attributes->merge(['class' => moox_button_classes($variant, $size, $disabled)]) }}
    x-data="{ loading: false }"
    @click="loading = true"
>
    <span x-show="!loading">
        @if($icon) <x-moox-icon :name="$icon" /> @endif
        {{ $slot }}
    </span>
    <span x-show="loading" class="hidden">Loading...</span>
</button>
```

**Dynamic Styling via TailwindCSS:**

Moox buttons automatically inherit styles based on the active theme:

```php
function moox_button_classes($variant, $size, $disabled) {
    return collect([
        'base' => 'inline-flex items-center justify-center rounded-md transition focus:outline-none focus:ring-2',
        'sizes' => [
            'sm' => 'px-3 py-1 text-sm',
            'md' => 'px-4 py-2 text-base',
            'lg' => 'px-6 py-3 text-lg',
        ],
        'variants' => [
            'solid' => 'bg-primary text-white hover:bg-primary-dark',
            'outline' => 'border border-gray-500 text-gray-500 hover:bg-gray-100',
            'gradient' => 'bg-gradient-to-r from-primary to-secondary text-white',
        ],
        'disabled' => $disabled ? 'opacity-50 cursor-not-allowed' : ''
    ])
    ->mapWithKeys(fn($value, $key) => [$key => $value[$$key] ?? ''])
    ->implode(' ');
}
```

### Ajax Components

Since Moox integrates Alpine Ajax, components can perform server interactions without page reloads.

-   Handles AJAX form submission without a full page reload.
-   Alpine Ajax automatically manages loading states.
-   Supports validation errors seamlessly.

**Example: Moox Ajax Form**

```php
<form x-data="{ submitting: false }" @submit.prevent="AlpineAjax.post('/submit', $refs.form, { loading: true })">
    <input type="text" name="name" placeholder="Your Name" class="moox-input" />
    <x-moox-button type="submit">Submit</x-moox-button>
</form>
```

### Modal Component

A fully interactive modal with AlpineJS state management.

```php
<x-moox-modal title="Delete Item" x-data="{ open: false }">
    <p>Are you sure you want to delete this item?</p>
    <x-moox-button variant="danger" @click="open = false">Cancel</x-moox-button>
</x-moox-modal>

<div x-show="open" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-lg font-semibold">{{ $title }}</h2>
        <div class="mt-4">{{ $slot }}</div>
    </div>
</div>
```

## Moox Base Theme

A lightweight TailwindCSS and AlpineJS-based theme that includes only functional styles, such as basic colors and borders, ensuring flexibility for child themes. Themes can define content width settings via configuration or their ServiceProvider:

```php
return [
    'content_width' => 'max-w-3xl', // Tailwind max-width
];
```

## Moox Featherlight

A customizable child theme using Tailwind, Alpine, and Alpine-Ajax. It is designed to be simple and fast while offering a fully styled user experience.

## Moox Slug

Permalink Management and automatic slug generation implemented in all Moox Items and Taxonomies.

## Moox Navigation

Navigation Management, allowing themes to define and structure navigations. Moox Navigation provides Blade/Alpine components:

```blade
<x-moox-navigation name="header" />
```

## Moox Entities

Content is structured through configurable entities:

-   **Moox Items**: Includes Page, Post, and Products.
-   **Moox Taxonomy**: Includes Category (high-performance nested set) and Tag.
-   **Moox Module**: Extends entities with features like SEO.

Entities must provide a `Frontend.php` class to define rendering behavior, including content width:

```php
class Frontend extends MooxFrontend
{
    public function getTemplate(): string
    {
        return 'moox::page.default'; // Blade template to render
    }

    public function getSEO(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->excerpt,
        ];
    }

    public function getMetaTags(): string
    {
        return "<meta name='description' content='{$this->excerpt}' />";
    }

    public function getContentWidth(): string
    {
        return config('moox.theme.content_width', 'max-w-full'); // Default content width
    }
}
```

## Moox Theme Inheritance

Themes can extend a base theme by defining a parent in their configuration:

```php
return [
    'name' => 'Moox Featherlight',
    'extends' => 'moox-base-theme',
];
```

### **View Resolution Order**

When rendering a view, Moox will first check the child theme, then fall back to the parent theme:

```php
function getThemeViewPath($view)
{
    $theme = config('moox.theme.active', 'moox-base-theme');
    $parent = config("moox.themes.$theme.extends");

    if (view()->exists("themes.$theme.$view")) {
        return "themes.$theme.$view";
    }

    if ($parent && view()->exists("themes.$parent.$view")) {
        return "themes.$parent.$view";
    }

    return "themes.moox-base-theme.$view"; // Default fallback
}
```

### **Asset Inheritance**

Assets will load from the child theme if available, otherwise fallback to the parent theme:

```php
function mooxThemeAsset($path)
{
    $theme = config('moox.theme.active', 'moox-base-theme');
    $parent = config("moox.themes.$theme.extends");

    if (file_exists(public_path("themes/$theme/$path"))) {
        return "themes/$theme/$path";
    }

    if ($parent && file_exists(public_path("themes/$parent/$path"))) {
        return "themes/$parent/$path";
    }

    return "themes/moox-base-theme/$path"; // Default fallback
}
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

## Moox Frontend

The core of the frontend system, integrating entities, themes, and routes.

### Localization

Localization is a core feature of Moox. It is implemented in the `Localization` package. To help implement automatic routing, we might use https://github.com/mcamara/laravel-localization.

### Performance Optimization

Initially, Moox Frontend will use standard Laravel routes. Future optimizations may include cached permalinks (`Cache::put('moox_slugs', $array)`) for high-speed lookups, JSON-based caching for ultra-fast retrieval, or static route file generation for extreme performance.

Another potential solution is **static HTML generation**, allowing requests to be served entirely by the webserver or CDN, bypassing Laravel and eliminating database queries.

### Develop feature

Lastly, Moox Frontend should ship a development smart login (just a simple passphrase, set in config) to hide the frontend while developing the hottest shit. That should also switch to an unfriendly robots.txt etc.

## Moox Search

Implements Laravel Scout. Offers faaceted search and filtering.

## Moox SEO

Instead of just being a Module, this package also adds other SEO features, like sitemap-generation, canonical URLs, OpenGraph and Schema.org JSON-LD.
