![Moox Company](https://github.com/mooxphp/moox/raw/main/art/banner/record.jpg)

# Moox Company

ERP-style company entity (customers, suppliers, subsidiaries). No payment fields, no `employee_id`, no default-address foreign keys — addresses and commercial terms use pivots (`addressables`, `employee_assignments`, `commercial_term_assignments`).

## Features

- UUID primary key, soft deletes, JSON `data`
- Parent / subsidiary hierarchy (`parent_id`)
- Config-driven statuses, company types, Filament tabs, scopes
- `address` / `addresses` relations via config + `moox/address` pivot config
- Filament resource, factory, Pest tests

## Installation

```bash
composer require moox/company
php artisan moox:install
```

## Factory

```php
Company::factory()->customer()->create();
Company::factory()->withParent($parent)->create();
```

## Configuration

Publish and edit `config/company.php` for statuses, company types, navigation group, tabs, and relation labels.
