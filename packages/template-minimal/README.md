# Template Minimal

A minimal Laravel template package with Tailwind CSS, DaisyUI, and Vite for modern, responsive web applications.

## Overview

`template-minimal` is a Laravel package that provides a complete template system with the following features:

- **Tailwind CSS v4** - Modern utility-first CSS framework
- **DaisyUI** - Component library for Tailwind CSS
- **Vite** - Fast build tool for frontend assets
- **Blade Components** - Reusable UI components
- **Responsive Layouts** - Mobile-first design
- **Dark Mode Support** - Automatic theme switching

## Installation

### Composer

The package is installed via Composer as a local package:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/template-minimal",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "moox/template-minimal": "*"
    }
}
```

Installation:

```bash
composer require moox/template-minimal
```

### NPM Dependencies

Install the frontend dependencies:

```bash
cd packages/template-minimal
npm install
```

## Configuration

### Activate Template

The template is activated via the `config/theme.php` configuration:

```php
'active_template' => env('APP_TEMPLATE', 'TemplateMinimal'),
```

Or in the `.env` file:

```env
APP_TEMPLATE=TemplateMinimal
```

### Views Namespace

The template uses a view namespace `theme` that is registered in the `AppServiceProvider` and points to the views of the active template:

```php
// In AppServiceProvider
$template = config('theme.active_template');
$templatePath = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $template));
View::addNamespace('theme', base_path("packages/{$templatePath}/resources/views"));
```

Alternatively, you can also use the package namespace directly: `template-minimal::`

## Structure

```
template-minimal/
├── resources/
│   ├── css/
│   │   └── app.css          # Tailwind CSS & DaisyUI Styles
│   ├── js/
│   │   └── app.js           # JavaScript Entry Point
│   └── views/
│       ├── components/      # Blade Components
│       │   ├── default-card.blade.php
│       │   └── hero-card.blade.php
│       ├── layouts/
│       │   └── app.blade.php # Main Layout
│       └── pages/           # Example Pages
│           ├── index.blade.php
│           └── blog/
│               ├── index.blade.php
│               └── show.blade.php
├── src/
│   ├── Components/         # PHP Component Classes
│   │   ├── DefaultCard.php
│   │   └── HeroCard.php
│   └── TemplateServiceProvider.php
├── public/
│   └── build/              # Built Assets (Vite)
├── vite.config.js          # Vite Configuration
├── tailwind.config.js      # Tailwind/DaisyUI Configuration
└── package.json            # NPM Dependencies
```

## Development Setup

### Vite Development Server

For development with Hot Module Replacement (HMR):

```bash
cd packages/template-minimal
npm run dev
```

The Vite dev server runs by default on `http://localhost:5173` and automatically watches for changes.

### Build Assets

For production builds:

```bash
cd packages/template-minimal
npm run build
```

The built assets are stored in `public/build/`.

## Tailwind CSS & DaisyUI

### Configuration

The package uses **Tailwind CSS v4** with the new CSS-based configuration:

```css
/* resources/css/app.css */
@import 'tailwindcss';

@source '../**/*.blade.php';
@source '../**/*.js';
@source '../../src/**/*.php';

@plugin "daisyui";
```

### DaisyUI Themes

DaisyUI is configured with light and dark mode:

```javascript
// tailwind.config.js
daisyui: {
    themes: ['light', 'dark'],
    darkTheme: 'dark',
    base: true,
    styled: true,
    utils: true,
}
```

### Dark Mode

The layout supports automatic dark mode based on system preferences or session storage:

```blade
<!-- Automatic Theme Switching -->
<script>
    const sessionTheme = sessionStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (sessionTheme === 'dark' || (!sessionTheme && prefersDark)) {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
</script>
```

## Vite Integration

### Manifest Path

The package uses its own Vite manifest in the `public/build/` directory. The `@vite` directive is automatically configured:

```blade
<!-- resources/views/layouts/app.blade.php -->
@vite(['resources/css/app.css'], $templateMinimalBuildPath ?? '../packages/template-minimal/public/build')
```

The build path is automatically provided by the `TemplateServiceProvider` and works both in local development and when installed as a Composer package.

### Vite Configuration

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css'],
            buildDirectory: 'public/build',
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        outDir: 'public/build',
        manifest: 'manifest.json',
        emptyOutDir: true,
    },
});
```

## Using Components

### Blade Components

The package registers components with the `moox` prefix:

```blade
<!-- Default Card -->
<x-moox-default-card :item="$item" />

<!-- Hero Card -->
<x-moox-hero-card :item="$item" />
```

### Example: Default Card

```blade
@if ($item)
    <x-moox-default-card :item="$item" />
@endif
```

Or directly with DaisyUI components:

```blade
@if ($item)
    <x-moox-card class="overflow-hidden bg-white rounded-md shadow-sm dark:bg-slate-800">
        <a class="w-full md:flex" href="#">
            <figure class="w-full md:w-64">
                <img src="{{ $item['image'] ?? 'default.jpg' }}" alt="Image" />
            </figure>
            <x-moox-card-body>
                <div class="pb-5">
                    <p class="mb-3 text-xs text-gray-500">{{ $item['datum'] ?? '' }}</p>
                    <x-moox-card-title class="text-primary-500">
                        {{ $item['title'] ?? '' }}
                    </x-moox-card-title>
                    <p>{{ $item['description'] ?? '' }}</p>
                </div>
            </x-moox-card-body>
        </a>
    </x-moox-card>
@endif
```

## Using Views

### Using Layout

```blade
@extends('theme::layouts.app')

@section('content')
    <h1>My Page</h1>
    <!-- Content here -->
@endsection

@section('aside')
    <aside>
        <!-- Sidebar Content -->
    </aside>
@endsection
```

### Example Pages

The package includes example pages in `resources/views/pages/`:

- `index.blade.php` - Homepage
- `blog/index.blade.php` - Blog overview
- `blog/show.blade.php` - Blog detail

## Customization

### Adding Custom Styles

Extend `resources/css/app.css`:

```css
@import 'tailwindcss';

@source '../**/*.blade.php';
@source '../**/*.js';
@source '../../src/**/*.php';

@plugin "daisyui";

/* Custom Styles */
.my-custom-class {
    /* ... */
}
```

### Creating Custom Components

1. Create a PHP component class in `src/Components/`:

```php
<?php

namespace Moox\TemplateMinimal\Components;

use Illuminate\View\Component;

class MyComponent extends Component
{
    public function render()
    {
        return view('template-minimal::components.my-component');
    }
}
```

2. Create the Blade view in `resources/views/components/my-component.blade.php`

3. The component will be automatically registered and available as `<x-moox-my-component />`.

## Build Process

### Development

```bash
# In package directory
cd packages/template-minimal
npm run dev
```

### Production

```bash
# Build assets
npm run build

# Assets are stored in public/build/
```

### Assets in Repository

The built assets (`public/build/`) should **not** be committed to the repository (see `.gitignore`).

## Troubleshooting

### Vite Manifest Not Found

Make sure the assets have been built:

```bash
cd packages/template-minimal
npm run build
```

### Tailwind Classes Not Applied

1. Check the `@source` directives in `resources/css/app.css`
2. Make sure Vite is running: `npm run dev`
3. Clear the cache: `php artisan view:clear`

### Components Not Found

1. Check if the component class exists
2. Make sure the namespace is correct: `Moox\TemplateMinimal\Components`
3. Run: `php artisan package:discover`

## Dependencies

### PHP
- Laravel Framework
- Moox Core Package

### JavaScript
- Vite ^7.0.7
- Tailwind CSS ^4.0.0
- @tailwindcss/vite ^4.0.0
- Laravel Vite Plugin ^2.0.0
- DaisyUI ^5.5.5

## License

MIT

## Support

For questions or issues, please create an issue in the repository.
