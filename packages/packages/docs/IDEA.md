# Moox Package Management System

## 1. Overview

This document outlines the package management system for Moox, including installation, activation, and metadata tracking. The system distinguishes between **installation status** and **entity relationships**, ensuring flexibility and maintainability.

---

## 2. Package Status Definitions

### 2.1 Install Status

Tracks whether a package is installed and available in Moox.

| Status      | Meaning                                                                         |
| ----------- | ------------------------------------------------------------------------------- |
| `available` | The package exists in `/packages/` but is **not installed via Composer**.       |
| `installed` | The package is installed via Composer but **not yet wired into Moox/Filament**. |
| `active`    | The package is **registered in Moox and Filament**, or simply its in a panel.   |

### 2.2 Update Status

Tracks whether a package is up-to-date or not.

| Status           | Meaning                                                    |
| ---------------- | ---------------------------------------------------------- |
| `up-to-date`     | The package is up to date.                                 |
| `update-pending` | The package will be automatically updated.                 |
| `needs-update`   | The package is not up to date and auto-update is disabled. |
| `update-failed`  | The package had an error when updating.                    |

---

## 3. Installation Workflow

### 3.1 Installation Process

1. \*\*A package is placed in \*\***`/packages/`**
    - Moox detects it as `available` but not yet installed via Composer.
2. **Composer Installs the Package**
    - Moox updates it to `installed`.
3. **Moox Registers the Package**
    - It becomes `active` when Filament loads its components.
4. **User Disables the Package (Optional)**
    - Moox does not unregister it from Composer, but it is no longer loaded in Filament.

---

## 4. Database Schema

### 4.1 Packages Table (`moox_packages`)

| Column                | Type      | Description                          |
| --------------------- | --------- | ------------------------------------ |
| `id`                  | UUID      | Unique identifier                    |
| `title`               | String    | Human-readable package title         |
| `slug`                | String    | URL-friendly slug                    |
| `name`                | String    | Technical package name               |
| `vendor`              | String    | Vendor name (e.g., `moox`)           |
| `version_installed`   | String    | Installed version                    |
| `installed_at`        | Timestamp | When the package was first installed |
| `installed_by_id`     | UUID      | User who installed it                |
| `installed_by_type`   | String    | Model type (`User`, `System`)        |
| `updated_at`          | Timestamp | When last updated                    |
| `update_scheduled_at` | Timestamp | When will it update                  |
| `updated_by_id`       | UUID      | User who updated it                  |
| `updated_by_type`     | String    | Model type (`User`, `System`)        |
| `installation_status` | Enum      | `available`, `installed`, `active`   |
| `auto_update`         | Boolean   | Whether auto-updates are enabled     |
| `is_theme`            | Boolean   | Whether this package is a theme      |

✅ **Relationships**

-   `hasMany` **Entities**, **Panels**, **Jobs**, **Mails**
-   `belongsTo` **Category**
-   `belongsToMany` **Tags**
-   `hasMany` **Relations**
-   `hasMany` **Taxonomies**
-   `hasMany` **Modules**
-   `hasMany` **Jobs**
-   `hasMany` **Mails**

---

### 4.2 Package Entities Table (`moox_package_entities`) (Astrotomic Translatable)

| Column            | Type      | Description                                            |
| ----------------- | --------- | ------------------------------------------------------ |
| `id`              | UUID      | Unique identifier                                      |
| `package_id`      | UUID      | References `moox_packages.id`                          |
| `type`            | Enum      | `item`, `record`, `draft`, `tag`, `category`, `module` |
| `frontend`        | Boolean   | Whether this entity has a Frontend class               |
| `created_at`      | Timestamp | When the entity was created                            |
| `created_by_id`   | UUID      | User who created it                                    |
| `created_by_type` | String    | Model type (`User`, `System`)                          |
| `updated_at`      | Timestamp | When last updated                                      |
| `updated_by_id`   | UUID      | User who updated it                                    |
| `updated_by_type` | String    | Model type (`User`, `System`)                          |

✅ \*\*Translations Table (`moox_package_entity_translations`)

| Column        | Type   | Description                           |
| ------------- | ------ | ------------------------------------- |
| `id`          | UUID   | Unique identifier                     |
| `entity_id`   | UUID   | References `moox_package_entities.id` |
| `locale`      | String | Locale (`en`, `de`, etc.)             |
| `name`        | String | Translatable name                     |
| `slug`        | String | Translatable slug                     |
| `description` | String | Translatable description              |

✅ **Relationships**

-   `belongsTo` **Categories**
-   `belongsToMany` **Tags**
-   `belongsToMany` **Panels**
-   `hasMany` **Fields**
-   `hasMany` **Relations** (pivot table)
-   `hasMany` **Taxonomies** (pivot table)
-   `hasMany` **Modules** (pivot table)

---

### 4.3 Package Entity Fields Table (`moox_package_entity_fields`) - translatable

| Column            | Type      | Description                                   |
| ----------------- | --------- | --------------------------------------------- |
| `id`              | UUID      | Unique identifier                             |
| `entity_id`       | UUID      | References `moox_package_entities.id`         |
| `field_name`      | String    | Column name                                   |
| `field_type`      | String    | Data type (e.g., `string`, `integer`, `json`) |
| `is_nullable`     | Boolean   | Whether the field allows `NULL` values        |
| `default_value`   | String    | Default value (if any)                        |
| `created_at`      | Timestamp | When the field was added                      |
| `created_by_id`   | UUID      | User who created it                           |
| `created_by_type` | String    | Model type (`User`, `System`)                 |
| `updated_at`      | Timestamp | Last updated timestamp                        |
| `updated_by_id`   | UUID      | User who updated it                           |
| `updated_by_type` | String    | Model type (`User`, `System`)                 |

---

## 5. Configuration Export & Management

The database reflects package configurations, but the final source of truth is stored in config/moox.php.

Configuration Includes:

-   Resources (Entities mapped to packages)
-   Tabs & Queries (Stored in the DB but exported to config)
-   Navigation Groups (Organized for Filament UI)
-   Relations, Taxonomies, and Modules (Mapped in DB, then saved to config)

✅ Workflow:

-   Users configure packages in Filament UI.
-   Configurations are stored in the database.
-   On clicking “Publish Config”, Moox exports the settings to config/moox.php.
-   The system reads from config at runtime for performance.

## Plan

-   Create a common package api in config
-   Create the package management, read-only
-   Create the other, entities, panels, themes, jobs, mails read-only
-   Implement installation
-   Implement updates
-   Create the package registry from package manager
-   Add GitHub and release management on moox.org
-   Allow to create packages, entites and panels ... fields, tabs, wire
-   Allow updating without compromising code, allow publishing
