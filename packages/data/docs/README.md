# Moox Locale - The world at your fingertips

Moox Locale is a powerful Laravel and Filament package that equips developers with a comprehensive dataset of countries, languages, currencies, and timezones. Designed to meet the needs of international businesses and applications, it aligns with global standards and offers deep insights into localized data.

## Why Moox Locale?

Whether you're building a global e-commerce platform, a multilingual SaaS application, or simply need accurate data for internationalization, Moox Locale has you covered. Its rich dataset and seamless integration with Laravel and Filament make it an essential tool for developers while providing decision-makers with the confidence of accurate and up-to-date information.

## Key Features

-   Comprehensive Data about countries, languages, currencies, and timezones.
-   Global Standards Compliance with ISO 4217, ISO 3166, and Unicode CLDR
-   Deep Insights using Subsets on tax, address formatting, historical data, and much more
-   Easy Integration using Laravel and Filament, making implementation seamless for devs

## Simple examples

```php
use App\Models\StaticCountry;
use App\Models\StaticCurrency;

// Retrieve a country by its alpha2 code
$germany = StaticCountry::where('alpha2', 'DE')->first();

// Access native and common names
echo $germany->common_name; // Germany
echo $germany->native_names['de']; // Deutschland

// Get currency details
$currency = StaticCurrency::where('code', 'EUR')->first();
echo $currency->common_name; // Euro
```

## Entities and Data

### Countries

-   Core Information: Alpha codes, region, subregion, population, area, and more.
-   Unique Details: Address formatting, postal code validation, driving side, embargo data.

### Languages

-   Comprehensive Data: Includes native and exonym names, direction (LTR/RTL), and associated countries.
-   Deep Insights: Alternate and historical names stored in a structured format.

### Currencies

-   Detailed Information: Subunits, decimal places, and native/exonym names.
-   Flexibility: Handles countries with multiple active currencies and historical data.

### Timezones

-   Accurate Data: Includes DST support, offsets, and applicable countries.

## Installation

```bash
composer require moox/locale
```

## Data Sources

Moox Locale is built on reliable and recognized sources:

-   [REST Countries](https://restcountries.com/) - Provides foundational data for countries, languages, currencies, and timezones.
-   [Unicode CLDR](https://cldr.unicode.org/) - Offers additional insights, exonyms, scripts, and translations.
