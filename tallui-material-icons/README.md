# TALLUI Material Icons

Offers over 2500 [Material Icons](https://fonts.google.com/icons) as SVG, used in **TALL**UI AdminPanel and on the website. 

The package can be used solely with Laravel or as part of [**TALL**UI](https://tallui.io).

## Usage

Simple like this:

```html
<x-icon-s-heart class="h-6 w-6 text-red-600" />
```

or this

```php
@svg('s-heart', 'w-6 h-6')
```

or this

```php
{{ svg('heart') }}
```

where

`s-heart` is for solid, `o-heart` is for outline and `heart` depends on configuration. The icons come without with and height and the color is set to currentColor, so it is pretty easy to give them the style you want.

## Configuration

Besides using Tailwind CSS, you are able to use the **TALL**UI Designer API, that inherits colors from Core up to every component. You can set that configuration in Core or any **TALL**UI package that ships components, templates or themes.

```php
return [
    'icon-defaults' => [
        'style' => 'outline', // solid or outline
        'icon-size' => 'md',
        'color' => 'primary',
        'icon-background' => 'avatar';
    ],
    'icon-sizes' => [
        'xs' => 'width-16 height-16',
        'sm' => 'width-20 height-20',
        'md' => 'width-24 height-24',
        'lg' => 'width-30 height-30',
        'xl' => 'width-46 height-46',
    ],
    'colors' => [
        'primary' => 'blue-500',
        'secondary' => 'gray-500',
        'text' => 'gray-700',
        'background' => 'blue-500',
        'button' => 'gray-500',
        'success' => 'gray-500',
        'warning' => 'gray-700',
        'error' => 'blue-500',
        'marketing' => 'gray-500',
        'teaser' => 'from blue-200 to blue-500',
    ],
    'dark-colors' => [
        'primary' => 'blue-500',
        'secondary' => 'gray-500',
        'text' => 'gray-700',
        'background' => 'blue-500',
        'button' => 'gray-500',
        'success' => 'gray-500',
        'warning' => 'gray-700',
        'error' => 'blue-500',
        'marketing' => 'gray-500',
        'teaser' => 'from blue-200 to blue-500',
    ],
	'icon-backgrounds' => [
        'avatar' => 'rounded-md';
    ]
];
```

## Styling

Besides simple styling with Tailwind CSS classes, there are many possibilities to get your icon components styled:

Depending on configuration you are able to shortcode icons including colors and sizes like this:

```html
<x-icon-dashboard size="lg" color="primary" background="avatar" />
```

or even much shorter, for example as a button with translatable text:

```html
<x-tui-button icon="left">
	<x-icon-dashboard-lg-primary-avatar /> ????
    {{ __('Dashboard') }}
</x-tui-button>
```

for following result:

