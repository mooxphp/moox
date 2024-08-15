![Moox Permission](https://github.com/mooxphp/moox/raw/main/art/banner/permission.jpg)

# Moox Permission

This is my package permission

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/permission
php artisan mooxpermission:install
```

Curious what the install command does? See manual installation below.

## What it does

<!--whatdoes-->

Here are some things missing, like an overview with screenshots about this package, or simply a link to the package's docs.

<!--/whatdoes-->

## Manual Installation

Instead of using the install-command `php artisan mooxpermission:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="permission-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="permission-config"
```

## Using the Default Policy

The default policy handles all defaults for Moox Resources in Filament:

```php
<?php

namespace Moox\Permission\Policies;

use App\Models\User;

class DefaultPolicy
{
    public function viewAll(User $user)
    {
        return $user->hasPermissionTo('view all');
    }

    public function editAll(User $user)
    {
        return $user->hasPermissionTo('edit all');
    }

    public function deleteAll(User $user)
    {
        return $user->hasPermissionTo('delete all');
    }

    public function create(User $user)
    {
        return $user->hasPermissionTo('create');
    }

    public function viewOwn(User $user, $model)
    {
        return $user->hasPermissionTo('view own') && $model->user_id === $user->id;
    }

    public function editOwn(User $user, $model)
    {
        return $user->hasPermissionTo('edit own') && $model->user_id === $user->id;
    }

    public function deleteOwn(User $user, $model)
    {
        return $user->hasPermissionTo('delete own') && $model->user_id === $user->id;
    }

    public function emptyTrash(User $user)
    {
        return $user->hasPermissionTo('empty trash');
    }

    public function changeSettings(User $user)
    {
        return $user->hasPermissionTo('change settings');
    }
}
```

The default policy is used by most Moox packages.

If you use Moox Builder to create a package, the default policy works out of the box and all default permissions are pre-configured to sane defaults.

## Extending the Default Policy

If you need to create a policy for a specific resource, you can extend the DefaultPolicy and override any methods where custom logic is required.

```php
use Moox\Permission\Policies\DefaultPolicy;

class ItemPolicy extends DefaultPolicy
{
    // Custom logic for editing own items
    public function editOwn(User $user, $item)
    {
        // Maybe add additional checks here
        return parent::editOwn($user, $item);
    }

    // Additional custom methods if needed
}

```

You then need to register the policy in the published Moox Core config (/config/core.php):

```php
return [
    'packages' => [
        'audit' => [
            'package' => 'Moox Audit',
            'models' => [
                'Audit' => [
                    'policy' => \Moox\Audit\Policies\AuditPolicy::class,
                ],
            ],
        ],
        // more packages
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
