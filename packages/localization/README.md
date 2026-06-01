![Moox Localization](https://github.com/mooxphp/moox/raw/main/art/banner/localization-package.jpg)

# Moox Localization

This is a package for Laravel to handle localization and requires the package [astrotomic/laravel-translatable](https://github.com/Astrotomic/laravel-translatable).

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/localization
php artisan localization:install
```

Curious what the install command does? See manual installation below.

## What it does

- Creates the `localizations` table (language/locale routing, admin/frontend visibility, fallback behaviour, per-row display settings).
- Integrates with `astrotomic/laravel-translatable` and `moox/core`.
- Provides a **language selector** for Filament (flags + display names) and a Livewire **language switch** component.
- Optional Localization Filament panel (`enable-panel` in config).

## Language selector: names, flags, and settings

How Moox builds **display names** and **flag icons** for each localization, and how the Filament toggles relate to each other.

### Where this appears

| Place | Attribute | Purpose |
|-------|-----------|---------|
| Admin language dropdown (`lang-selector` blade) | `display_name`, `display_flag` | Current language + list of alternatives |
| Localization index table | `display_name`, `table_flag` | Overview of all locales |
| Translation columns (Draft, News, Media, …) | `display_flag` | Per-locale flag in translation UI |
| Filament create/edit form | Toggles + `use_country_icon` | Configure each localization |

The Livewire `language-switch` component currently shows **language codes only** (no flags). Use `display_flag` when adding flags there.

### Admin language selector (`lang-selector`)

For Filament resources with translations (Draft, News, Category, Media, etc.):

```blade
@include('localization::lang-selector')
```

Or register a render hook on your panel. The view loads active localizations (`is_active_admin` or `is_active_frontend`), shows `display_flag` and `display_name` on the trigger, and lists alternatives the same way. Pass `locale_variant` as the `lang` query parameter so the correct regional entry is selected.

### Two separate concerns

**Names** and **flags** are configured independently:

```
display_name  ←  use_native_names
                  show_regional_variants
                  use_country_translations

display_flag  ←  use_country_icon
                  (+ language-specific exceptions, see below)
```

**Regional** affects **names only**, not flags. Country vs language flags are controlled only by **Country flag** (`use_country_icon`).

### Database: `localizations` table

| Column | Type | Default | Role |
|--------|------|---------|------|
| `locale_variant` | string | required | e.g. `de_CH`, `en_US`, `fr_CH` |
| `use_native_names` | boolean | `true` | Native vs English language name |
| `show_regional_variants` | boolean | `true` | Append country from locale in name |
| `use_country_translations` | boolean | `true` | Translated country name in parentheses |
| `use_country_icon` | boolean | `false` | Country vs language flag icon |

### Display name (`display_name`)

Built by `Localization::getDisplayNameAttribute()`.

#### Native (`use_native_names`)

| On | Off |
|----|-----|
| `StaticLanguage::native_name` | `StaticLanguage::common_name` (usually English) |
| Deutsch | German |

#### Regional (`show_regional_variants`)

When **on** and `locale_variant` contains `_` (e.g. `de_CH`), appends the country in parentheses:

| On | Off |
|----|-----|
| Deutsch (Schweiz) | Deutsch |
| English (United States) | English |

No effect when the locale has no region suffix (`de` only).

#### Country names (`use_country_translations`)

Only when **Regional** is on. Turning Regional off also turns Country names off. Controls the country part in parentheses:

| On | Off |
|----|-----|
| Translated name from `static_countries.translations` | `StaticCountry::common_name` |
| Deutsch (Schweiz) | Deutsch (Switzerland) |

#### Name examples for `de_CH`

| Native | Regional | Country names | Result |
|--------|----------|---------------|--------|
| off | off | — | German |
| on | off | — | Deutsch |
| on | on | off | Deutsch (Switzerland) |
| on | on | on | Deutsch (Schweiz) |

### Display flag (`display_flag` / `table_flag`)

Built by `Localization::resolveFlagIcon()`. `display_flag` and `table_flag` always use the **same logic**.

#### Country flag (`use_country_icon`)

| Value | Behaviour for `de_CH` |
|-------|------------------------|
| `false` (default) | Language flag → `flag-de` |
| `true` | Country flag from locale suffix → `flag-ch` |

Country code = part after `_` in `locale_variant`, lowercased. Icon is used only if it exists (`flagExists()`).

#### Languages with a dedicated flag

Always use their language-specific icon, regardless of `use_country_icon`:

`ku`, `bo`, `eo`, `eu`, `cy`, `br`, `co`, `ar`, `aa`

#### Swiss site example (`de_CH`, `it_CH`, `fr_CH`)

| locale_variant | `use_country_icon` | Flag shown |
|----------------|--------------------|------------|
| `de_CH` | `false` | DE |
| `it_CH` | `false` | IT |
| `fr_CH` | `false` | FR |

With `use_country_icon` on all three, all show CH—usually not desired for a multilingual Swiss site.

### Filament resource toggles

| Column label | Database column | Affects |
|--------------|-----------------|---------|
| Native | `use_native_names` | Name |
| Regional | `show_regional_variants` | Name |
| Country names | `use_country_translations` | Name (parentheses) |
| Country flag | `use_country_icon` | Flag icon |

### Programmatic access

```php
use Moox\Localization\Models\Localization;

$localization = Localization::where('locale_variant', 'de_CH')->first();

$localization->display_name; // e.g. "Deutsch (Schweiz)"
$localization->display_flag; // e.g. "flag-de"
$localization->table_flag;    // same as display_flag

$localization->show_regional_variants;
$localization->use_country_icon;
```

### Recommended defaults

| Scenario | Native | Regional | Country names | Country flag |
|----------|--------|----------|---------------|--------------|
| Swiss multilingual (`de_CH`, `it_CH`, `fr_CH`) | on | on | on | **off** |
| Simple list, no region in UI | on | off | — | off |
| Emphasize country over language (rare) | on | on | on | **on** |

### Related code

| File | Responsibility |
|------|----------------|
| `src/Models/Localization.php` | Names + flag resolution |
| `resources/views/lang-selector.blade.php` | Admin dropdown |
| `src/Filament/Resources/LocalizationResource.php` | Form and table toggles |
| `config/localization.php` | Filament labels, navigation, panel |
| `database/migrations/create_localizations_table.php.stub` | Schema |

## Localization panel

Enable in config:

```php
'enable-panel' => true,
```

Panel URL: `/localization`

## Language Switcher (Livewire)

Relies on locales marked active for admin or frontend. Text codes only today.

Filament panel hook:

```php
->renderHook(
    \Filament\View\PanelsRenderHook::USER_MENU_BEFORE,
    fn (): string => \Illuminate\Support\Facades\Blade::render('@livewire(\'language-switch\',[\'context\'=>\'backend\'])'),
)
```

Blade:

```blade
@livewire('language-switch', ['context' => 'backend'])
```

## Tabs and translation

Moox Core features like Dynamic Tabs and Translatable Config. See the config file; [Moox Core docs](https://github.com/mooxphp/core/blob/main/README.md#dynamic-tabs) explain tabs.

## Manual installation

```bash
php artisan vendor:publish --tag="localization-migrations"
php artisan migrate

php artisan vendor:publish --tag="localization-config"
```

## Astrotomic translatable

We require [astrotomic/laravel-translatable](https://docs.astrotomic.info/laravel-translatable).

## Changelog

See [CHANGELOG](CHANGELOG.md).

## Security

See [security policy](https://github.com/mooxphp/moox/security/policy).

## License

MIT — see [LICENSE](LICENSE.md).
