---
name: moox-tree
description: >-
  Integrates moox/tree into Moox consumer packages (installation, Filament assets,
  TreeIndexConfiguration, forwardFromResource, TreeIndexListRecords,
  RendersAsTreeIndexInspector, Filament plugin). Use only when the user explicitly
  invokes moox-tree skill or asks to integrate moox/tree into a resource.
disable-model-invocation: true
---

# moox/tree Integration Skill

Skill for **wiring `moox/tree` into consumer packages** (e.g. `category`, `menu-builder`).

**Out of scope (integration workflow):** implementing changes inside `packages/tree/**` — use `.cursor/rules/moox-tree-package.mdc`. After such package work, sync this skill via `.cursor/rules/moox-package-skill-sync.mdc` (registry entry `tree` → `moox-tree`).

## Invocation

Load only when the user **explicitly** requests it (e.g. “use moox-tree skill”, `/moox-tree`).

## Required workflow

1. **Check scope** — Does the task touch `packages/tree/src/**`? → Stop; point the user to the package rule.
2. **Install package & assets** — Follow [installation.md](installation.md): Composer dependency, service provider, `php artisan filament:assets`. Skip only if already verified in this project.
3. **Gather context** — Consumer package, model, existing table resource, migration/columns.
4. **Decision tree** — For each branch in [decisions.md](decisions.md): if context does not make the choice obvious, use **`AskQuestion`**. Do not guess.
5. **Toolbar (mandatory before implementation)** — Clarify search, filters, and language switcher in **one** `AskQuestion` with three questions ([decisions.md §6](decisions.md#6-toolbar-suche-filter-sprach-switcher)) unless the user already specified. **Only then** create resource/config.
6. **Implement integration** — Apply the chosen path from [integration.md](integration.md).
7. **Verify** — Run the checklist in [integration.md](integration.md#verification-checklist) and the install items in [installation.md](installation.md#installation-checklist-before-integration-code).

## Decision branches

| # | Topic | When to ask | Details |
|---|--------|-------------|---------|
| 1 | Tree mode | Model/migration unclear | [decisions.md §1](decisions.md#1-baum-modus) |
| 2 | Resource pattern | Greenfield vs. existing resource | [decisions.md §2](decisions.md#2-resource-muster) |
| 3 | List binding | No `table()` or special case | [decisions.md §3](decisions.md#3-listen-anbindung) |
| 4 | Inspector | No edit page / label+parent only | [decisions.md §4](decisions.md#4-inspector) |
| 5 | Missing capability | Not solvable via config | [decisions.md §5](decisions.md#5-fehlende-fähigkeit) |
| 6 | Toolbar | **Always before `treeIndex()`** — search/filter/language not given by user | [decisions.md §6](decisions.md#6-toolbar-suche-filter-sprach-switcher) |

**AskQuestion rule:** One question per branch with 2 options (yes/no or 2–3 sensible variants). Implement only after answers. If multiple branches are open, use **one AskQuestion with multiple questions** (max. 2–3 at once) or ask sequentially.

**Toolbar block (default before every new tree resource):** One `AskQuestion` with exactly these three questions — skip only if the user **explicitly** stated otherwise (e.g. “without language switcher”):

1. Search in the toolbar?
2. Filters/tabs from the list resource?
3. Language switcher?

## Fixed rules (no question needed)

- **No tree mechanics in the consumer** — no custom Livewire for tree CRUD, no move/reorder actions, no copied Alpine stores.
- **No model extensions only for tree** — no `getTreeLabel()`, no `TreeNodeContract`; allowed: columns, Kalnoy `NodeTrait` for nested set, domain accessors → then `labelColumnQueryable(false)`.
- **Inspector `$resource`** points to the **base resource** (form source), **not** `XxxTreeResource`.
- **List page** `extends TreeIndexListRecords` — config is registered in `mount()`.
- **Panel:** register tree resource via Filament plugin.
- **Domain filters** only via config closures (`modifyQuery`, `applySearchUsing`, `applyLanguageUsing`) or `forwardFromResource()` — do not push category/tenant queries into the package.
- **No consumer CSS/JS for tree UI** — layout and Alpine store come from `moox/tree` via Filament assets; see [installation.md](installation.md).

## References in the repo

| What | Path |
|------|------|
| Installation & assets | [installation.md](installation.md) |
| Gold standard Moox | `packages/category/src/Resources/CategoryTreeResource.php` |
| List page + tabs | `packages/category/src/Resources/CategoryResource/Pages/TreeListCategories.php` |
| Inspector | `packages/category/src/Resources/CategoryResource/Pages/TreeInspectorCategory.php` |
| Plugin | `packages/category/src/Plugins/CategoryTreePlugin.php` |
| Package docs | `packages/tree/README.md` |
| Integration rule | `.cursor/rules/moox-tree-integration.mdc` |
| Service provider | `packages/tree/src/TreeServiceProvider.php` |
| Tree CSS | `packages/tree/resources/css/tree.css` |
| Alpine store | `packages/tree/resources/views/scripts/alpine-tree-store.blade.php` |

## After integration

Run the checklist in [integration.md](integration.md#verification-checklist). Add tests only if the user requests them.
