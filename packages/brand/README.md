<div class="filament-hidden">

![Moox Jobs](banner.jpg)

</div>

# Moox Brand

Welcome to the official Moox brand package — find our logo, signet, banners and information here.

This is a Laravel package that also ships every kind of image or blade components to be used on the Moox website, demo or any other Laravel platform that needs Moox branding.

## Installation

Moox Brand will automatically be installed with Moox Core and every other package that needs our identity.

Installing Moox is as easy as:

```bash
composer require moox/brand
php artisan moox:install
```

## The Moox Brand

**Moox** is:

-   A short `.org` domain I (Alf) registered 20 years ago to build something awesome

-   Besides **moox.org**, we also own **moox.de**, **moox.pro** and **moox.press**

-   A once-popular TYPO3 extension set, developed by me and my former company DCN

-   A name that works great with tech terms (e.g. _Moox Blog_, _Moox News_, _Moox Builder_)

-   A verb in the making (“Let me moox your website”)

We write **Moox**, not ~~moox~~, not ~~MOOX~~, even if the logo does it wrong ;-)

## The Moox Logo

Our logo is the infinity symbol — simple, memorable, and hinting at future-proof flexibility.

It looks perfect on dark blue and plays well with bright backgrounds even snow white.

<p align="center">
    <br>
    <img src="./public/logo/moox-logo.png" width="200" alt="Moox Logo">
    <br>
    <br>
</p>

It can lose color (remember fax?) without losing its identity.

<p align="center">
    <br>
    <img src="./public/logo/moox-logo-monochrome.png" width="200" alt="Moox Logo">
    <br>
    <br>
</p>

_"How does infinity fit Moox? We’ll know in a few decades."_ 😉

## The Moox Signet

The Moox Signet works on dark and light backgrounds.

<p align="center">
    <br>
    <img src="./public/signet/signet.png" width="200" alt="Moox">
    <br>
    <br>
</p>

We have an alternative version for special purposes.

<p align="center">
    <br>
    <img src="./public/signet/signet-alt.png" width="200" alt="Moox">
    <br>
    <br>
</p>

When used as avatar image, we include the blue gradient background.

<p align="center">
    <br>
    <img src="./public/signet/avatar-on-blue.jpg" width="200" alt="Moox">
    <br>
    <br>
</p>

Or if you really need a white background.

<p align="center">
    <br>
    <img src="./public/signet/avatar-on-white.jpg" width="200" alt="Moox">
    <br>
    <br>
</p>

## The Moox Banner

All our packages have a consistent banner — useful for fast visual context.

-   Primary size: **2560×1440**
-   Required by: [filamentphp.com](https://filamentphp.com/)
-   Compatible with: GitHub, Packagist, VS Code, and beyond
-   See the example in this README.md or in any other package.

## Moox ASCII Art

For Artisan commands (and others) we use ASCII Art that ships with a trait in this package.

```php
<?php

namespace Moox\Brand\Console\Traits;

use function Laravel\Prompts\info;

trait Art
{
    public function art(): void
    {
        info('

						Weird looking code, nice ASCII art, we sware! ;-)

        ');
    }
}

```

## The Moox Bot

Moox Bot is the identity of our GitHub Bot and he is always around, when browsing Moox.org. He is a nice and helpful robot.

He is used as Avatar:

<p align="center">
    <br>
    <img src="./public/robot/mooxbot-avatar.jpg" width="200" alt="Moox">
    <br>
    <br>
</p>

Or flying around:

<p align="center">
    <br>
    <img src="./public/robot/2-attention.png" width="200" alt="Moox">
    <br>
    <br>
</p>

Sometimes hiding:

<p align="center">
    <br>
    <img src="./public/robot/80-box.png" width="200" alt="Moox">
    <br>
    <br>
</p>

## The Moox Colors

We haven't defined the colors exactly as we only use them as gradients but those are picked from our logo and banner:

-   **Violet:** #600ab1
-   **Pink:** #860c94
-   **Dark-blue:** #01081b
-   **Gradient-to:** #190fd6

## The Moox Fonts

-   The Moox logo uses **Exo Soft**
-   Our website and Admin Panels use **Exo 2**
-   For documentation we use **Noto Sans**
-   For code we use **Noto Sans Mono**
-   Headings should be kept in **Exo 2** or **Exo**
-   All fonts are available on [Google Fonts](https://fonts.google.com/).

## The Copyright

While most of our packages are FOSS (Free Open Source) and licensed under the permissive MIT license, our identity is protected by Copyright. Please use this package and its contents in a respectful way.

**Copyright 2001 - 2025 Alf Drollinger and the Moox Team**
