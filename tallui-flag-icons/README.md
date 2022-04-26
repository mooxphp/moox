# TALLUI Flag Icons

Offers over 250 countries or languages as SVG flags in 4 styles: circle, square, 4:3 rectangular or preserved in original size. Uses [Circle Flags](https://hatscripts.github.io/circle-flags/) from Hatscripts, [Country Flags](https://hampusborgos.github.io/country-flags/) from Hampusborgos and [Country Flags in SVG](https://flagicons.lipis.dev/) from Lipis.

The package can be used solely with Laravel or as part of [**TALL**UI](https://tallui.io).

## Usage

Simple like this:

```html
<x-flag-c-de class="h-6 w-6 text-red-600" />
```

or this

```php
@svg('flag-us', 'w-6 h-6')
```

or this

```php
{{ svg('flag-s-fr') }}
```

where

`c-de` is for Germany circle, `o-us` is for US original, `r-gb` is for British rectangular, and `s-fr` is for France square, while the unprefixed `pl` depends on your configuration.

## Configuration

- default-style: c/o/r/s = c
- default-sizes:
  - c = 
  - o = 
  - r = 
  - s = 

## List of countries

Coming soon.