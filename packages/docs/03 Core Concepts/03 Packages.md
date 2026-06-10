---
title: Packages
description: Moox package types, Composer extra config, and the Build command for entities, taxonomies, modules.
---

# Packages

Moox packages are packages built and maintained by Moox or by the community that implement the MooxServiceProvider and Composer extra configuration:

```json
{
  "extra": {
    "moox": {
      "name": "Moox Publish",
      "type": "entity",
      "template": {
        "context": "Create a publishable and translatable entity for Moox.",
        "built_from": "Record",
        "alternates": "Post, Page",
        "replace": {
          "Moox": "%Vendor%",
          "moox": "%vendor%",
          "Publish": "%Package%",
          "publish": "%package%",
          "This is a publishable item.": "%Description%"
        }
      }
    }
  }
}
```

Moox is modular: each package has a single responsibility. Most packages contain one entity, one taxonomy, one module, or one feature, so you can mix, replace, or extend them when building your app.

## Package types

| Type         | Description                          | Examples                                 |
| ------------ | ------------------------------------ | ---------------------------------------- |
| core         | Core packages                        | moox/core, moox/media, moox/localization |
| ai           | AI Layer                             | moox/ai, moox/mcp                        |
| bundle       | Set of packages, admin panel, config | moox/cms                                 |
| entity       | Single filament resource             | moox/item, moox/publish, moox/post       |
| taxonomy     | Single filament resource taxonomy    | moox/tag, moox/category                  |
| module       | Module to extend any entity          | moox/module, moox/seo                    |
| plugin       | More than one thing, has features    | moox/jobs, moox/sync                     |
| devtool      | Developer tool                       | moox/build, moox/dev                     |
| block        | Single editor block                  | moox/table-block                         |
| blockset     | Set of editor blocks                 | moox/blocks                              |
| component    | Single frontend component            | moox/table-component                     |
| componentset | Set of components                    | moox/components                          |
| theme        | Frontend theme                       | moox/featherlight-theme                  |
| helper       | Laravel or Filament helper           | moox/progress                            |
| iconset      | Blade icons                          | moox/laravel-icons                       |
| server       | Central server package               | moox/devops                              |
| press        | Moox Press + WordPress plugin        | moox/press                               |
| data-legacy  | Data provider (legacy static data)   | moox/data-legacy    |

## Build command

When the `template` section is present, developers and AI can use the Build command to create entities and relations, taxonomies, modules, a theme and a bundle.

# Moox Package Management System

## 1. Overview

This document outlines the package management system for Moox, including installation, activation, and metadata tracking. The system distinguishes between **installation status** and **entity relationships**, ensuring flexibility and maintainability.

---

## 2. Package Status and Type Definitions

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

### 2.3 Package Types

Which kind of package are we dealing with.

| Type              | Meaning                                          |
| ----------------- | ------------------------------------------------ |
| `moox_package`    | The package is officially made by Moox.          |
| `moox_compatible` | The package is compatible with Moox package API. |
| `moox_dependency` | The package is directly required by Moox.        |
| `filament_plugin` | The package is a Filament plugin.                |
| `laravel_package` | The package has a Laravel Service Provider.      |
| `php_package`     | The package is a PHP package.                    |

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
| `installed_by_id`     | ID        | User who installed it                |
| `installed_by_type`   | String    | Model type (`User`, `System`)        |
| `updated_at`          | Timestamp | When last updated                    |
| `update_scheduled_at` | Timestamp | When will it update                  |
| `updated_by_id`       | ID        | User who updated it                  |
| `updated_by_type`     | String    | Model type (`User`, `System`)        |
| `install_status`      | Enum      | `available`, `installed`, `active`   |
| `update_status`       | Enum      | `up-to-date`, ... (see above)        |
| `auto_update`         | Boolean   | Whether auto-updates are enabled     |
| `is_theme`            | Boolean   | Whether this package is a theme      |
| `package_type`        | Enum      | `moox_package`, ... (see above)      |
| `activation_steps`    | JSON      | `Migrated`, `Seeded`, `Configu...`   |

✅ **Relationships**

- `belongsTo` **Category**
- `belongsToMany` **Tags**
  **later**
- `hasMany` **Entities**, **Panels**, **Jobs**, **Mails**
- `hasMany` **Relations**
- `hasMany` **Taxonomies**
- `hasMany` **Modules**
- `hasMany` **Jobs**
- `hasMany` **Mails**

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

- `belongsTo` **Categories**
- `belongsToMany` **Tags**
- `belongsToMany` **Panels**
- `hasMany` **Fields**
- `hasMany` **Relations** (pivot table)
- `hasMany` **Taxonomies** (pivot table)
- `hasMany` **Modules** (pivot table)

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

- Resources (Entities mapped to packages)
- Tabs & Queries (Stored in the DB but exported to config)
- Navigation Groups (Organized for Filament UI)
- Relations, Taxonomies, and Modules (Mapped in DB, then saved to config)
