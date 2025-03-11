# Moox Frontend Concept

Moox Frontend is a modular system designed to provide a frontend for existing Moox packages like Media, Localization, Post, Page, and Taxonomy. It enables seamless content management by extending entities and integrating configurable themes. Moox Core remains backend-focused, while the frontend is handled separately through the following packages:

## Tooling

-   Laravel 11 - [https://laravel.com/docs/11.x](https://laravel.com/docs/11.x)
-   Alpine JS - [https://alpinejs.dev/](https://alpinejs.dev/)
-   Alpine Ajax - [https://alpine-ajax.js.org/](https://alpine-ajax.js.org/)
-   TailwindCSS 4 - [https://tailwindcss.com/](https://tailwindcss.com/)
-   PenguinUI - [https://www.penguinui.com/](https://www.penguinui.com/) - as inspiration

## Moox Components

Renderless Blade and AlpineJS components, providing structure and logic without enforcing styles. Moox Components must implement content width definitions for entities:

```blade
<div class="{{ config('moox.theme.content_width', 'max-w-full') }}">
    {{ $slot }}
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
