---
title: Users
description: Moox User package — Filament UserResource as a Record entity with soft delete.
---

# Users

Admin user management lives in `moox/user`.

## Entity type

`UserResource` extends `BaseRecordResource` (not plain `BaseResource`).

**Why:** Users use SoftDeletes, restore, and a deleted tab — that is the Record pattern. The `User` model stays `Authenticatable` (it cannot extend `BaseRecordModel`).

Pages use Record bases (`BaseListRecords`, `BaseCreateRecord`, `BaseEditRecord`, `BaseViewRecord`). Form actions come from the Record schema (`getFormActions()`), not page footer boilerplate.

Soft-delete list behaviour is owned by Core (`BaseResource` + trash tabs). Details: Core Concepts → Entities.

## Config

- `user.readonly` — disables create/edit/delete/restore when true
- `user.resources.user.tabs` — includes `all` and `deleted`
