# TALLUI Form Components W-I-P

**TALL**UI Form Components is a Laravel package that ships 23 Blade components for building forms. The package can be used solely with Laravel or as part of [**TALL**UI](https://tallui.io).

## Components

-   [Button](components/Button.md)
-   [ButtonSend](components/ButtonSend.md)
-   [Checkbox](components/Checkbox.md)
-   [ColorPicker](components/ColorPicker.md)
-   [DatePicker](components/DatePicker.md)
-   [DateRangePicker](components/DateRangePicker.md)
-   [Fieldset](components/Fieldset.md)
-   [FileUpload](components/FileUpload.md)
-   [Form](components/Form.md)
-   [Input](components/Input.md)
-   [InputEmail](components/InputEmail.md)
-   [InputPassword](components/InputPassword.md)
-   [Label](components/Label.md)
-   [MarkdownEditor](components/MarkDownEditor.md)
-   [MultiSelect](components/MultiSelect.md)
-   [RadioGroup](components/RadioGroup.md)
-   [Range](components/Range.md)
-   [Repeater](components/Repeater.md)
-   [RichTextEditor](components/RichTextEditor.md)
-   [Select](components/Select.md)
-   [Textarea](components/Textarea.md)
-   [Toggle](components/Toggle.md)
-   [Validation](components/Validation.md)

## Features

**TALL**UI Components offer

-   Simple Blade syntax, modern templating
-   Basic Tailwind CSS classes
-   Individual styling with Tailwind CSS
-   Reactivity made with Livewire and Alpine
-   Accessibility through WAI Aria attributes
-   A consistent API for integration

## Usage

The components can be used as part of **TALL**UI or completely solely in any Laravel project. They are a short way to accomplish a consistent and reactive UI as well as a clear API to develop with these components.

**This example is from Web Components, should be a Basic Form either**

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

-   PHP 8.1
-   [Laravel](https://laravel.com/) 9
-   [Laravel Livewire](https://laravel-livewire.com/) 2
-   [Alpine.js](https://alpinejs.dev/) 3
-   [Tailwind CSS](https://tailwindcss.com/) 3
-   [Coloris](https://coloris.js.org/) used by ColorPicker
-   [Flatpickr](https://flatpickr.js.org/) used by DatePicker
-   [Date Range Picker](https://www.daterangepicker.com/) used by DateRangePicker
-   [Dropzone](https://www.dropzone.dev/) used by FileUpload
-   [Easy MarkDown Editor](https://easy-markdown-editor.tk/) used by MarkdownEditor
-   [Quill](https://quilljs.com/) used by RichTextEditor
-   [Select2](https://select2.org/) used by Multiselect

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

-   Use '@tuiStyles' Within your <head>
-   and '@tuiScripts' right before your closing </body>

If you always want the directives to be executed, even when `app.debug` is disabled, you can force them to load the CDN links by passing a `true` boolean:

```html
@tuiStyles(true) @tuiScripts(true)
```

Libraries are only loaded for components that are enabled through the `components` config option.

## Configuration

By default all available components are enabled. If you want to change this behavior, you can publish the config-file

```shell
php artisan vendor:publish --tag=tallui-form-components-config
```

and disable components, that you don't want to use in your project

```php
return [
    'components' => [
        ...
        'tui-label' => Components\Forms\Label::class,
        ...
    ],
];
```

## Customization

The best way to customize **TALL**UI Components is with the use of Tailwind Classes and available Options. But to extend or replace features, you are able to Overwrite Components Classes and Views by publishing them:

```shell
php artisan tui:publish label
```

Optionally use the `--view` or `--class` flag, to only publish one of both parts of the component.

Artisan will create `app/View/Components/Forms/Label.php` and `resources/view/vendor/tallui-form-components/components/label.blade.php`.
