# Moox Scopes Package

Manage scopes in Filament. Scopes define visibility boundaries for records across the Moox ecosystem.

## Installation

Install the package using the Moox installer:

```bash
php artisan moox:install
```

## Features

- **Scope Management**: Create, edit, and manage scopes with origin, source, context, and boundary
- **Cascading Form**: Intelligent form that filters options based on previous selections
- **Scope Registry Integration**: Works with the Moox Core Scope Registry

## Config vs database

| | Panel navigation | Create scope form |
| --- | --- | --- |
| `scopes.allowed` (per parent resource) | Whitelist only — does not add menu items | Origin and source dropdowns are derived from it |
| `scopes.registry` | — | Origin keys and model classes |
| `scopes` table (`is_active`) | Scoped child nav items | Saved scopes; record scope select options |

When creating a scope, **origin** comes from `registry.origins` but is shown only if some parent’s `scopes.allowed` lists that origin and the origin resource is registered with `HasScopedModel`. **Source** is the parent key (e.g. `tag`) whose `allowed` block includes that origin.

Without e.g. `media` in `tag.resources.tag.scopes.allowed`, origin `media` will not appear in the form.

Full details: `packages/core/docs/scope-system-team-guide.md` (section 2 and 3.7).

## Requirements

- Laravel 12+
- Filament 4+
- Moox Core
