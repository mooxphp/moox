![Moox Backup Server](https://github.com/mooxphp/moox/raw/main/art/banner/backup-server.jpg)

# Moox Backup Server

Filament UI for [Spatie Laravel Backup Server](https://spatie.be/products/laravel-backup-server).

## Quick Installation

These two commmands are all you need to install the package:

```bash
composer require moox/backup-server
php artisan mooxbackupserver:install
```

Curious what the install command does? See manual installation below.

## What it does

<!--whatdoes-->

Filament UI for Spatie Laravel Backup Server. Create automated or make manual incremental backups. More information and screenshots will follow.

<!--/whatdoes-->

## Manual Installation

Instead of using the install-command `php artisan mooxbackup-server:install` you are able to install this package manually step by step:

```bash
// Publish and run the migrations:
php artisan vendor:publish --tag="backup-server-migrations"
php artisan migrate

// Publish the config file with:
php artisan vendor:publish --tag="backup-server-config"
```

## Usage

## SSH Connection

### Create ssh connection to your source server

Add SSH key to your server. Once your key is added shh into your instance.

For Forge users:
Open your Command Line and type `ssh forge@your-address`.

### Add source server ssh key to your destination server

Copy the public key from your source server and add it to your destination server.
Connect destination and source server.
Confirm fingerprinting from destination server.

## Create new Destination

name: the name of this destination

disk_name: the name of one of the disks configured in config\filesystems.php. The chosen disk must use the local driver

## Create new Source

Set a Name

Add your Hostname

Set the ssh User

Ssh Port should be 22

Copy ssh key path from source server (in case of forge: "~/.ssh/id_rsa")

The ssh user to get into the source server

Cron expression when to start a backup [(Cron Help)](https://crontab.guru/)

Choose a given destination

Pre_backup_commands:
To create a MYSQL Backup u need to cd in your folder and create a backup. Each value is a command that will be executed. For example:
| Key | Value |
| -------- | ------- |
| 0 | cd /home/forge/yourplatform.com/ |
| 1 | mysqldump DATABASE -uUSERNAME -pPASSWORD > dump.sql |
only change DATABASE USERNAME AND PASSWORD

Post_backup_commands:
Now do everything that should be executed after a backup. In this case we want to remove the dump from server.
| Key | Value |
| -------- | ------- |
| 0 | cd /home/forge/yourplatform.com/ |
| 1 | rm -f dump.sql |

Includes: specify a include path
| Key | Value |
| -------- | ------- |
| 0 |/home/forge/yourplatform.com/ |

To exlude paths you should give paths relative to the paths given in includes

## Creating Backups

Now you can create backups ether manual or automatical You just need to select the Source.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
