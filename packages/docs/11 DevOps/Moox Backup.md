# Moox Backup



Spatie Laravel Backup (or not)

- Backup tables
- Backup files
- Offer secure ZIP downloads
- Automatic backups
- Automatic restore
- Atomic switch databases for zero downtime data imports from API
  - Live_table + files
  - Backup_table + files
  - Import_table + files

- Comprehensive Health check
- Automatic fault recovery
- Status and notification 



So we need a lot of jobs and commands:

- Backup Database
- Restore Database
- Switch Database
- Prepare Database Download
- Backup Files (Media + ...)
- Restore Files (Media + ...)
- Switch Files (Media + ...)
- Prepare Files Download
- Backup Platform
- Prepare Platform Download
- Platform Installer (Shell?)
- Import from API (as an example)



Finally Backup would need an entity.



## Moox Stage

Instead of Backup, Restore and Import we can also use Stages

- Live
- Stage (Import)
- Restore (Backup)

Stages would be identical, but the DBs would all be used by real platforms, same with files.

Stages could be used by "Moox Stage", what would be a Stage Manager in Filament Panels as a Dropdown-Menu and maybe a status page or Dashboard widget as well as some commands and actions to control those stages. For stages it might be interesting to see the version of code and freshness of data at a glance.



Finally Stages would need an entitiy that must be on one platform (could be one of those three or the central platform) and an API to connect the others.



## Moox Devsource

Get the ZIP files from a (live) platform and import db and files ...