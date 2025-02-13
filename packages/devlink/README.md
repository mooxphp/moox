# Moox Devlink

This package is only for internal use.

It is used to link the packages from the `moox` monorepo into a project. It runs on MacOS, Linux and Windows.

## Installation

```bash
cp .env.example .env
composer require moox/devlink
php artisan vendor:publish --tag="devlink-config"
```

## Screenshot

![Moox Devlink](./devlink.jpg)

## How It Works

1. Prepare your project's `.gitignore` file:

```bash

# Ignore all files in packages/ (including symlinks)
packages/*
# Allow tracking of real directories inside packages/
!packages/**/
# Ensure empty directories can be committed
!packages/*/.gitkeep
# for windows
/packageslocal/*

```

2. Configure your paths and packages in the `config/devlink.php` file and the `.env` file, if needed (Windows users for example).

3. When running `devlink:link`:

    - Creates backup of original composer.json → composer.json.original
    - Creates symlinks for all configured packages
    - Updates composer.json with development configuration
    - Creates composer.json-deploy for production use
    - Asks to run `composer install`
    - Asks to run `php artisan optimize:clear`
    - Asks to run `php artisan queue:restart`

4. When running `devlink:deploy`:

    - Removes all symlinks
    - Deletes the packages folder, if empty
    - Restores production-ready composer.json from composer.json-deploy

5. CI Safety Net - `deploy.sh`:
    - If composer.json-deploy exists in the repository:
        - The script will restore it as composer.json
        - Commit and push the change in GH action
    - This ensures no development configuration reaches production

## Changing branches

If you need to change the branches for ANY of the involved repositories, you just need to run the command again, it will automatically update the symlinks for the current branch.

> ⚠️ **Important**  
> If you forget to run the command, when CHANGING BRANCHES ON ANY OF THE REPOS, you will surely run into a 500 error, that drives you nuts.

## Mac

Mac works out of the box. You can have local packages mixed with the symlinked packages in your `/packages` folder.

![Moox Devlink](./devlink-mix.jpg)

## Windows

On Windows there are most probably some issues with the symlinks. If you run into issues, you can either globally or project-wise disable the symlinks or do the following:

```php

    'packages_path' => 'packages-linked',

```

Devlink will then link the packages into the `packages-linked` folder.

## Roadmap

-   [ ] Test on Mac
-   [ ] Test on Windows
-   [ ] Test Deployment on Mac
-   [ ] Test Deployment on Windows
-   [ ] Implement automatic Deployment
-   [ ] Implement all 3 types of packages
-   [ ] If package is a symlink itself, ...?
-   [ ] If package is in multiple base paths...?

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
