# TALLUI Core

The Core package serves all global resources and requires all packages needed by **TALL**UI.

## Configuration

One of the main parts of **TALL**UI Core is the configuration that is shared to all packages that depend on the Core.

Besides using Tailwind CSS, you are able to use the **TALL**UI Designer API, that inherits colors from Core up to every component. You can set that configuration in Core or in any **TALL**UI package that ships components, templates or themes.

The Global Styling Configuration is also available in the page configuration of **TALL**UI Pages, means it can also be inherited to all child-pages.

### Global Styling

There are global colors and style options that may be used by all components, templates and themes.

- material-icons
  - default-style: solid / outline
- icon-sizes
  - xs =
  - sm =
  - md =
  - lg =
  - xl =
- colors
  - primary
  - ...

## Dependencies

- Laravel Jetstream - brings the TALL-Stack as well as some standardized features for authentication, registration, users and profiles.





## Style Layers



- Component = Base Styles
  - Theme = Colors, Roundings  ===== https://laravel.com/docs/9.x/blade#including-subviews
  - Template = Placements
    - Global Styling



Fiddle

ist :class = override und class= append?

```php
<x-input :class="$theme['input']" />
```

kann da auch eine function rein?

 @props

```php
$theme = [
	'form' => 
];
```

