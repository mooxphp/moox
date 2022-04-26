# TALLUI Web Components W-I-P

**TALL**UI Web Components is a Laravel package that ships 33 Blade components for building websites. 

The package can be used solely with Laravel or as part of [**TALL**UI](https://tallui.io).

## Components

- Accordion
- Alert
- Card
- Carousel
- Code
- Columns
- Countdown
- Divider
- Dropdown
- Embed
- File
- Files
- Flexbox
- Grid
- Heading
- Html
- HtmlTemplate
- Image
- Images
- List
- Markdown
- Modal
- Navigation
- Quote
- Section
- SectionDivider
- Slider
- Table
- Tabs
- Text
- Toast
- ToC
- Tooltip
- Video

## Features

**TALL**UI Components offer 

- Simple Blade syntax, modern templating
- Basic Tailwind CSS classes
- Individual styling with Tailwind CSS
- Reactivity made with Livewire and Alpine
- Accessibility through WAI Aria attributes
- A consistent API for integration 

## Usage

The components can be used as part of **TALL**UI or completely solely in any Laravel project. They are a short way to accomplish a consistent and reactive UI as well as a clear API to develop with these components.

One of the simplest examples is the ToC Component, that renders a list of anchor-links based on your contents headings:

```php
<x-tui-toc>{!! $html !!}</x-toc>
```

This will render to:

```html
<ul>
    <li>
    	<a href="#first-heading">First Heading</a>
        <ul>
            <li>
	            <a href="#first-sublevel">First Sublevel</a>
            </li>
        </ul>
    </li>
</ul>
```

**TALL**UI Components offer variants, options and usage of Tailwind CSS Classes:

```php
<x-tui-toc-md levels=3 class="list-disc list-inside">
# First Heading
I love **TALL**UI.

## First Sublevel
Because it makes fun.
</x-tui-toc-md>
```

## Requirements

If you use the components as part of **TALL**UI, the only requirement is **TALL**UI Core. If you want to use it solely, you can use Laravel Jetstream or require the TALL-Stack, means

- PHP 8.1
- [Laravel](https://laravel.com/) 9
- [Laravel Livewire](https://laravel-livewire.com/) 2
- [Alpine.js](https://alpinejs.dev/) 3
- [Tailwind CSS](https://tailwindcss.com/) 3
- [CommonMark](https://commonmark.thephpleague.com/) used by Markdown 
- [Splide](https://splidejs.com/) used by Slider 
- [Tall Toasts](https://github.com/usernotnull/tall-toasts) used by Toast 
- [Tippy.js](https://atomiks.github.io/tippyjs/) used by Tooltip
- [Highlight.js](https://github.com/highlightjs/highlight.js) used by Code

## Installation

Clear your config cache before installing a new package:

```bash
php artisan config:clear
```

Then install the package by running:

```bash
composer require usetall/tallui-web-components
```

### Including Scripts and Styles

Some of the **TALL**UI Components require additional CSS and JavaScript. If you are using **TALL**UI Core as dependency, it will automatically care for the inclusion and asset-pipelining. If you use it solely, you can use following directives:

- Use '@tuiStyles' Within your <head>
- and '@tuiScripts' right before your closing </body>

If you always want the directives to be executed, even when `app.debug` is disabled, you can force them to load the CDN links by passing a `true` boolean:

```html
@tuiStyles(true)
@tuiScripts(true)
```

Libraries are only loaded for components that are enabled through the `components` config option.

## Configuration

By default all available components are enabled. If you want to change this behavior, you can publish the config-file

```shell
php artisan vendor:publish --tag=tallui-web-components-config
```

and disable components, that you don't want to use in your project

```php
return [
    'components' => [
        ...
        'tui-toc' => Components\Toc\TocMarkdown::class,
        ...
    ],
];
```

## Customization

The best way to customize **TALL**UI Components is with the use of Tailwind Classes and available Options. But to extend or replace features, you are able to Overwrite Components Classes and Views by publishing them:

```shell
php artisan tui:publish toc
```

Optionally use the  `--view` or `--class` flag, to only publish one of both parts of the component.

Artisan will create `app/View/Components/Web/Toc.php` and  `resources/view/vendor/tallui-web-components/components/toc.blade.php`.