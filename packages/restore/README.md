# What it does 
depending on the Spatie Backup server the package will restore the Backups on the same server on another site. 

# Installation and Usage Guide for Moox Restore Package

**Installation**

1. Open your terminal and navigate to your project directory.
2. Run the following commands to install the packag:
```
composer require moox/restore
```

Step 3. and 4. can be replaced with the command 

```
php artisan php artisan moox-restore:install
```

3. Once the installation is complete, run the following command to publish the package's configuration file:
```
php artisan vendor:publish --provider="Moox\Restore\RestoreServiceProvider"
```
4. This will publish the `restore.php` configuration file to your project's `config` directory.

**Configuration**

1. Open the `config/restore.php` file and configure the package as needed. The configuration file includes settings for backup and restore operations, such as database connections, file paths, and job queue settings.
2. Make sure to update the `backup_host`, `sql_file_name`, `old_domain`, and `new_domain` settings to match your application's requirements.
3. Also, update the `queue_connection` and `queue` settings to specify the job queue to be used for the restore process.
4. In case of debugging just set the `debug_mode` config value to true and every step will be logged. 


**Usage**

In the UI, you can create a new destination by providing the following details:

* Host
* Source
* App URL
* App name
* Database name
* Database username
* Database password

Additionally, you can optionally specify:

* Redis queue
* Redis database
* Redis cache database

To include other environment variables that need to be updated or added to the environment file, simply add them to the JSON in the database.


**Available Commands**
**Scheduled Tasks**

* `php artisan moox-restore:dispatch-restore`: This command restores all destinations stored in the database. It is intended to be scheduled for automatic execution.
* `php artisan moox-restore:summary`: This command sends an email summarizing the backup and restore operations performed between 0-5 am.

**Manual Restoration**

* `php artisan moox-restore:restore {restoreBackup}`: This command initiates the restoration of a specific backup to its intended destination. It can be triggered manually through the UI or via an SSH connection.
- It begins by clearing all files from the destination folder.
- Next, it copies the files to their designated destination.
- The DotEnv file is then rewritten using parameters retrieved from the database.
- Finally, the Dump file is imported into the database connection.

**Feature Ideas**

- Download the Database in GUI
- Secure Files 

**License**

The Moox Restore package is open-sourced software licensed under the MIT license.

