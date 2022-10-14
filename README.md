# TallUI Monorepo

This is the TallUI Monorepo containing all packages and the Laravel dev app for instant development with Laravel Sail or Laragon.

```bash
# Use the prepared environment
cp .env.example .env

# Build
composer install

# Run Sail
./vendor/bin/sail up

# Run Vite (in Ubuntu, not in Sail container)
npm install
npm run dev

# Rebuild the sail config if needed
./vendor/bin/sail down --rmi all -v
php artisan sail:install

# Remove broken symlinks if needed
rm -Rf vendor/usetall
```

## Custom

You can require custom packages without overwriting the main composer.json:

```bash
cd _custom
cp composer.json-example composer.json
```

Now clone your package as a subrepo into _custom and edit composer.json to your needs:

```json
{
    "name": "usetall/tallui-custom",
    "description": "The TallUI Monorepo - a merged Composer.json for custom packages.",
    "keywords": ["framework", "laravel", "package", "custom", "composer", "monorepo"],
    "license": "MIT",
    "repositories": [
        {
            "type": "path",
            "url": "./_custom/package"
        }
    ],
    "require": {
        "custom/package": "dev-main"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

As with composer you can require multiple packages.


## Development

- Do `npm run build` when committing changes, because automated tests on GitHub needs a vite-manifest.
- Do `php artisan migrate --database=sqlite` to reflect changes to the test-database
- Check Larastan and https://phpstan.org/user-guide/baseline to get rid of errors


## Todo

- Concept for _custom: maybe https://stackoverflow.com/questions/46580522/composer-install-from-two-composer-json-file
- PHP CS Fixer from app to packages and builder
- Fix dev_components
- Update all packages and builder from Spatie
- Fix all packages workflows
- Scaffold all current packages
- Get all packages running in composer
- Wire the full-app with composer
