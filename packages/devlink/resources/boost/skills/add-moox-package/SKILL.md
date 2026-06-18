---
name: add-moox-package
description: >-
  Add, enable, or start using a package in a monorepo-consuming Laravel project — both
  Moox monorepo packages (moox/*) and the project's own packages. Use this
  skill whenever the user wants to "add a package", "enable moox/X", "use the X package",
  "pull in X from the monorepo", "neues Package hinzufügen", "Package aktivieren", or
  references wanting a new moox/* or own package available in the app. It performs the full,
  error-prone sequence (devlink config, composer.json path entry WITH the required versions
  option, package list export, composer update, verification, commit) so the dev doesn't do
  it by hand and doesn't hit the dev-dev/route-cache pitfalls. Trigger this proactively even
  if the user only names a package and an intent to use it.
---

# Add a package to this monorepo-consuming project

This project consumes `moox/*` packages **directly from the monorepo** (`mooxphp/moox`) via Composer `path` repositories — no Packagist, no release. This skill performs the complete sequence to add one, avoiding the pitfalls that otherwise cause CI/deploy failures.

> This skill ships with the `devlink` package (`resources/boost/skills/add-moox-package/`) and is installed via Laravel Boost. Background and rationale live in the devlink docs. This skill is the actionable checklist.

## Step 0 — Identify which kind of package

Two cases, different steps. Determine which before doing anything:

- **A Moox monorepo package** (`moox/<name>`) — lives in the `mooxphp/moox` monorepo, fetched/symlinked into `packages/`. Managed via `config/devlink.php`. **Most common case.**
- **The project's own package** (a `local`/committed package, e.g. `acme/<name>`) — committed in this repo under `packages/`, not from the monorepo.

If unclear, ask the user. Also confirm the exact package name and the folder name (they can differ, e.g. a package `acme/components` may live in folder `components` or `acme-components`).

---

## Case A — Add a Moox monorepo package

### A1. Activate it in `config/devlink.php`

Find the package's entry under `'packages'` (the array **key** is the folder name, e.g. `'address'`). Set it active:

```php
'<folder>' => [
    'active' => true,        // was false
    'path' => $public_base_path.'/<folder>',
    'type' => 'public',
],
```

If the entry doesn't exist, the package may not be a public monorepo package — stop and confirm with the user.

### A2. Symlink it locally

```bash
php artisan moox:devlink
```

This adds a `path` entry to `composer.json` — but **only with `symlink: true`, without the `versions` option**. The next step adds the missing piece.

### A3. Add/fix the `composer.json` entries

This is the **critical** step. Two parts:

**`require`** — add the package on `*`:

```json
"moox/<name>": "*"
```

**`repositories`** — the `path` entry MUST include `versions` forcing `dev-main`. `moox:devlink` does not add this, so add it manually:

```json
{
    "type": "path",
    "url": "packages/<folder>",
    "options": {
        "symlink": true,
        "versions": { "moox/<name>": "dev-main" }
    }
}
```

> **Why `versions` is non-negotiable:** without it, Composer derives the version from the surrounding project branch (`dev`) → `dev-dev`, but the monorepo packages require each other as `dev-main`, so resolution fails with `... requires moox/core dev-main ... do not match your constraint`. The `versions` option forces `dev-main`. Add it for **every** moox path entry; if other entries are missing it, add it there too (start with `moox/core`).

### A4. Regenerate the package list

```bash
php artisan moox:devlink-export
```

This rewrites `.github/moox-packages.txt` from the active `type: public` packages in `config/devlink.php`. CI and the deploy script read this file. Confirm the new package now appears in it.

### A5. Resolve

```bash
composer update "moox/*"
```

Expect a clean resolution. If you see `dev-dev` errors, a `versions` option is missing (go back to A3). If you see "could not find package", the folder isn't in `packages/` (was A2 run? is the folder name correct?).

### A6. Verify the app still boots (catches deploy-time failures early)

```bash
php artisan optimize:clear
php artisan optimize
```

`optimize` runs `route:cache`, `view:cache`, `config:cache` — the same steps the deploy runs. These surface problems invisible in normal local dev:

- `Unable to prepare route [...] Another route has already been assigned name [...]` → duplicate route name (often in the new package); find with `grep -rn "<name>" packages/ app/ routes/` and make one unique.
- `Unable to locate a class or view for component [...]` → a referenced component/class from a package that isn't registered/installed.

Resolve any failure here before committing — otherwise the deploy fails at this step.

### A7. Commit

Commit exactly these (do **not** commit the `packages/<folder>` contents — moox packages are gitignored):

```bash
git add composer.json composer.lock .github/moox-packages.txt config/devlink.php
git commit -m "Add moox/<name> package"
```

Suggested message style: `feat: use moox/<name>` or `chore: add moox/<name> package`.

---

## Case B — Add the project's own package

Own packages (a `local`/committed package, e.g. `acme/<name>`) are committed in this repo, not fetched from the monorepo.

### B1. `.gitignore` exception

`packages/*` is ignored; re-include the own package so it's versioned:

```gitignore
!/packages/<folder>/
```

### B2. `composer.json` entries

**`require`:**

```json
"<vendor>/<name>": "*"
```

**`repositories`** — path entry with `versions` (use the branch the package actually develops on, usually `dev-main`):

```json
{
    "type": "path",
    "url": "packages/<folder>",
    "options": {
        "symlink": true,
        "versions": { "<vendor>/<name>": "dev-main" }
    }
}
```

> Confirm the package **name** from `packages/<folder>/composer.json` (`grep '"name"' packages/<folder>/composer.json`) — it can differ from the folder name. `require` uses the name, the `path` url uses the folder.

### B3. Resolve, verify, commit

```bash
composer update <vendor>/<name>
php artisan optimize:clear && php artisan optimize    # verify boot (see A6)
git add composer.json composer.lock .gitignore packages/<folder>
git commit -m "Add <vendor>/<name> package"
```

Note: for own packages you **do** commit the `packages/<folder>` contents (unlike moox packages).

---

## Important rules (apply to both cases)

- **Never edit package code in `packages/` or `vendor/` on a server** — it's overwritten on deploy. Moox package code is edited in the monorepo; own package code is edited in `packages/<folder>` locally and committed.
- **`--no-dev` on deploy:** consumed packages must be in `require`, not `require-dev`. Only dev-only tooling (`moox/devlink`, `moox/devtools`) belongs in `require-dev`.
- **Folder name vs package name** differ for some packages — keep `require` (name) and `path`/list (folder) straight.
- **Don't run `moox:deploy`** in this project — it would strip the path setup.
- If `php artisan moox:devlink-export` reports an empty or wrong list, check that the package is `active: true` AND `type: public` in `config/devlink.php` (bundles, `private`, `local` are intentionally excluded).

## Done

After committing, the package is available locally, and on the next CI run / deploy the sparse checkout will fetch it (moox) or the committed files will be present (own). Remind the user to push so CI/deploy pick it up.