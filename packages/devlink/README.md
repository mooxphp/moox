# Moox Devlink

Moox Devlink is used to link packages a monorepo into any project and to deploy a production-ready composer.json. That allows us to develop Moox packages in any project. It runs on MacOS and Linux, Windows with special configuration.

## Installation

```bash
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

2. Configure your paths and packages in the `config/devlink.php` file and change the package path in the `.env` file, if needed (Windows users should set the `DEVLINK_PACKAGES_PATH` variable to `packageslocal`).

3. When running `devlink:status`:

    - Lists all packages that are currently devlinked
    - Lists all packages that are configured but not devlinked
    - Lists all packages that are not configured, but devlinked
    - Shows the configuration and the deploy status of each package

4. When running `devlink:link`:

    - Creates the packages folder, if it does not exist
    - Creates backup of original composer.json → composer.json.original
    - Creates symlinks for all configured packages
    - Updates composer.json with development configuration
    - Creates composer.json-deploy for production use
    - Asks to run `composer install`
    - Asks to run `php artisan optimize:clear`
    - Asks to run `php artisan queue:restart`

5. When running `devlink:unlink`:

    - Removes all symlinks
    - Deletes the packages folder, if empty
    - Creates a backup of composer.json to composer.json-backup
    - Restores original composer.json from composer.json-original
    - Asks to run `composer install`
    - Asks to run `php artisan optimize:clear`
    - Asks to run `php artisan queue:restart`

6. When running `devlink:deploy`:

    - Removes all symlinks
    - Deletes the packages folder, if empty
    - Creates a backup of composer.json to composer.json-backup
    - Restores production-ready composer.json from composer.json-deploy
    - Asks to run `composer install`
    - Asks to run `php artisan optimize:clear`
    - Asks to run `php artisan queue:restart`

7. CI Safety Net - `deploy.sh`:

    - If composer.json-deploy exists in the repository:
        - the script will restore it as composer.json
        - rename composer.json-original to composer.json-backup
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

```env
DEVLINK_PACKAGES_PATH=packages-linked
```

Devlink will then link the packages into the `packageslocal` folder instead of mixing them into packages.

## Classes

Please see the [CLASSES.md](./CLASSES.md) file for a quick class overview.

## Roadmap

Please see the [ROADMAP.md](./ROADMAP.md) file for what is planned.

## Changelog

Please see the [CHANGELOG.md](./CHANGELOG.md) file for what has changed.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
