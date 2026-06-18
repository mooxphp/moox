# Using Monorepo Composer Packages in a Separate Laravel Project — Without a Release

> **Goal:** Consume packages that live in a monorepo (e.g. `mooxphp/moox`) from a **separate project** — on the current `main` state, **without** subtree-splitting, **without** tagging a release per commit, and **without** publishing to Packagist. Locally the packages are symlinked live via `moox:devlink`; on CI and servers only the packages you actually use are fetched into the project's `packages/` directory. The full monorepo (apps, tooling) is never cloned — only the package folders you consume.

This is the approach the `devlink` package supports, including the `moox:devlink-export` command that keeps the package list in sync across environments. It has been used and **tested on Laravel Forge** with zero-downtime deployments.

---

## Why this way

Sometimes packages live only in a monorepo and should **not** go to Packagist (yet), and tagging a release per commit is not an option. That rules out the usual paths:

- **`dev-main` via a VCS / split repository** — Composer can't resolve monorepo subdirectories over VCS. It would require subtree-splitting plus a branch push per commit.
- **Private Packagist / Satis** — also requires a split/push step.
- **A "deploy" rewrite of `composer.json` to a Packagist-clean version** (the standard path some tooling offers, including Moox's own `moox:deploy`) — produces a `composer.json` that expects every package on **Packagist**. Unreleased monorepo packages aren't there, so `composer install` would abort or pull the wrong state. Not usable here.

What remains: consume the needed packages **directly from the monorepo** — locally via `moox:devlink` symlinks, on CI/servers via a targeted sparse checkout into `packages/`.

### The "but Packagist also has a dev-main" objection

True, but it doesn't help: Packagist's `dev-main` mirrors the `main` branch of the **split repo**, not the monorepo. If the split repos are only updated on release (no per-commit push), that `dev-main` is as stale as a tag — and unreleased packages aren't on Packagist at all. The `packages/` approach reads the monorepo state directly, without any push.

### Where each package comes from

| Package group | Source | Constraint |
|---|---|---|
| All consumed `moox/*` | monorepo → `packages/` | `*` (with global `minimum-stability: dev`) |
| Third-party (laravel, etc.) | Packagist (normal) | as usual (`^x`) |

Every monorepo package the project uses comes from the monorepo state — not Packagist. They are all symlinked (local) or fetched into `packages/` (CI/server). Only non-monorepo dependencies come from Packagist.

> For this to hold, **every** consumed `moox/*` package must be present in `packages/`. `path` repositories take priority over Packagist — but only for packages physically present in `packages/`. If one is missing, Composer would look for it on Packagist.

---

## Architecture overview

Three environments, **one** committed `composer.json` with `path` entries pointing into `packages/`. The only difference is *how* `packages/` gets filled.

| | Local dev | CI | Server (Forge) |
|---|---|---|---|
| `packages/` contains | **symlinks** (via `moox:devlink`) | real copies (sparse checkout) | real copies (sparse checkout) |
| source | central monorepo checkout | monorepo, only needed folders | monorepo, only needed folders |
| Composer | symlinks `vendor/moox/*` → `packages/*` | same (project-internal, stable) | same (project-internal, stable) |
| purpose | fast dev, changes live instantly | verify what gets deployed | running app (staging/prod) |

Because `packages/` lives **inside** the project, the `vendor/` symlinks are project-internal and stable — no `COMPOSER_MIRROR_PATH_REPOS` needed.

---

## The shared composer.json

`moox:devlink` writes **one `path` entry per package**. The **`versions` option** in each entry is essential — without it, resolution fails (see "The dev-dev trap").

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "packages/core",
      "options": {
        "symlink": true,
        "versions": { "moox/core": "dev-main" }
      }
    },
    {
      "type": "path",
      "url": "packages/category",
      "options": {
        "symlink": true,
        "versions": { "moox/category": "dev-main" }
      }
    }
    // ... one entry per package, each with its own name → dev-main
  ],
  "require": {
    "moox/core": "*",
    "moox/category": "*",
    "moox/user": "*"
    // ... all consumed moox/* on "*"
  },
  "minimum-stability": "dev",
  "prefer-stable": true
}
```

> **Two things must line up:** The `require` entry (`*`) matches *any* version — but that alone isn't enough, because the monorepo packages require **each other** as exactly `dev-main`. The `versions` option forces every path package to report `dev-main`, so those internal cross-references resolve too.

### How Composer resolves this

- Each `path` entry points at a package folder; Composer reads its `composer.json` and matches on the `"name"` field.
- `path` repositories take **priority over Packagist** — a package found in `packages/` wins. So every consumed `moox/*` must be present in `packages/`.
- `"symlink": true` is the local variant (symlink into the central monorepo). On servers/CI the folders are real `rsync` copies — the symlink then points project-internally at the copy (stable). If a server has trouble with symlinks in `vendor/`, set `COMPOSER_MIRROR_PATH_REPOS=1` so Composer copies into `vendor/` instead.

### Stability & versions: why `versions: dev-main` is required (the "dev-dev trap")

Composer derives a path package's version from the **git branch of the surrounding repository**. The package folders themselves have no own `.git` (symlink or copy), so Composer looks at the **consuming project** — and if that's on a branch called `dev`, the packages get version **`dev-dev`**.

But the monorepo packages require each other as exactly **`dev-main`** (e.g. `moox/category` → `"moox/core": "dev-main"`). `dev-dev` ≠ `dev-main`, so resolution fails:

```
moox/category dev-dev requires moox/core dev-main ->
moox/core[dev-dev] from path repo (packages/core) has higher repository priority ...
do not match your constraint
```

The **`versions` option per entry** overrides the derived `dev-dev` with `dev-main` and fixes exactly this. Locally it works even without it (the symlink resolves into the monorepo, whose branch is `main` → `dev-main`); copies on CI/servers have no git context, so the explicit `versions` is what makes them resolve.

For the **root requires**, all `moox/*` stay on `"*"` plus `minimum-stability: dev` + `prefer-stable: true`: `*` matches the forced `dev-main`, dev stability is allowed, and all non-monorepo dependencies stay stable.

> **Why the monorepo version always wins:** `path` repositories are **canonical** in Composer 2 — a package found in `packages/` is never pulled from Packagist. So `prefer-stable` can't accidentally fetch a stable release of these packages; it only affects the real Packagist dependencies.

> When you `devlink` a **new** package, its path entry is added with `symlink: true` only — **without** `versions`. Add the `versions` option manually, or the package comes in as `dev-dev` and fails. Existing entries are not overwritten, so manually added `versions` persist.

### .gitignore (keep your own packages, ignore the monorepo ones)

`packages/` holds two kinds of packages: your **own** project packages (commit them) and the **injected** monorepo packages (symlink/copy — never commit). Ignore everything, then re-include your own:

```gitignore
# Composer
/vendor/

# packages/ : ignore everything ...
/packages/*
!/packages/.gitkeep

# ... but keep your OWN packages (your project-owned packages):
!/packages/your-own-package/
!/packages/another-own-package/

# optional, if a deploy variant is generated:
composer.json-deploy
```

Listing your own packages as exceptions is usually the smaller, more stable list — the monorepo folders stay ignored automatically. `/packages/*` only matches the top level, so the committed contents of your own packages stay fully versioned.

---

## Single source of truth: `moox:devlink-export`

The list of consumed packages otherwise lives in several places (devlink config, CI workflow, deploy script). The `moox:devlink-export` command makes `config/devlink.php` the single source: it writes the active package folder names to a committed file that CI and the deploy script read.

The export path is configurable, so each consuming project decides where it goes. Add it in `config/devlink.php` next to the other path declarations:

```php
/*
|--------------------------------------------------------------------------
| Export Path
|--------------------------------------------------------------------------
|
| The file the package list is exported to (relative to the project root),
| used by CI and the deploy script. Can be set in the .env file.
|
*/
$export_path = env('DEVLINK_EXPORT_PATH', '.github/moox-packages.txt');

// ... in the returned array, next to the other path keys:
'export_path' => $export_path,
```

The command writes the **active** packages of `type: public` (the ones that live in the monorepo), excluding bundles (meta packages, no folder), `private` (from Satis) and `local` (your own packages):

```php
<?php

namespace Moox\Devlink\Commands;

use Illuminate\Console\Command;

class ExportPackagesCommand extends Command
{
    protected $signature = 'moox:devlink-export {--path= : Override the export path from config}';
    protected $description = 'Write the active devlink package folders to a file (for CI/deploy)';

    public function handle(): int
    {
        $path = $this->option('path')
            ?? config('devlink.export_path', '.github/moox-packages.txt');

        // In config/devlink.php the array KEY is the folder name (e.g. "address").
        // Export only active packages that live in the monorepo: type "public".
        // This excludes bundles (meta packages, no folder), "private" (from Satis)
        // and "local" (your own committed packages).
        $folders = collect(config('devlink.packages', []))
            ->filter(fn ($cfg) => ($cfg['active'] ?? false) === true
                               && ($cfg['type'] ?? null) === 'public')
            ->keys()
            ->sort()
            ->values();

        $target = base_path($path);
        @mkdir(dirname($target), 0755, true);   // create .github/ if it doesn't exist
        file_put_contents($target, $folders->implode(' ') . PHP_EOL);

        $this->info($folders->count() . " active packages → {$path}");
        $this->line($folders->implode(' '));

        return self::SUCCESS;
    }
}
```

Generation runs **locally** (where the app boots) and the file is committed; CI/servers only read it. This avoids a chicken-and-egg problem (the command would otherwise need `vendor/`, which only exists after the list is known).

> The committed `composer.json` (`require` + `repositories` with `versions`) still has to match the list manually. The export command could be extended to generate the `repositories` entries too, making `config/devlink.php` the only source — that's a larger command (parsing the existing `composer.json`, preserving your own packages and JSON formatting). The list file solves the most acute duplication first.

### Bundled agent skill: `add-moox-package`

Adding a package is a multi-step sequence with two easy-to-miss pitfalls (the `versions: dev-main` option, and verifying the app still boots via `optimize`). The devlink package ships an **agent skill** that performs the whole sequence so a developer doesn't do it by hand:

```
devlink/resources/boost/skills/add-moox-package/SKILL.md
```

It's installed via **Laravel Boost** (Boost discovers skills under `resources/boost/skills/`). Once installed, an assistant triggers it on intents like "add a package", "enable `moox/X`", "use the X package", and runs the full flow: activate in `config/devlink.php` → `moox:devlink` → add the `composer.json` `path` entry **with `versions`** + `require` → `moox:devlink-export` → `composer update "moox/*"` → verify with `php artisan optimize` → commit. It handles both monorepo packages and the project's own packages, and encodes the pitfalls (the `dev-dev` trap, folder-name vs. package-name, `--no-dev`/`require` placement) so they don't resurface at deploy time.

---

## Setup A — Local development (`moox:devlink`)

The central monorepo lives in a local checkout. `moox:devlink` symlinks the configured packages from there into `packages/`.

```bash
cd path/to/consuming-project

# publish/adjust the devlink config once (config/devlink.php)
php artisan vendor:publish --tag="devlink-config"

# symlink the configured packages from the central monorepo
php artisan moox:devlink

composer install
```

`moox:devlink` creates `packages/<pkg>` as a **symlink** to the monorepo checkout; Composer then symlinks `vendor/moox/*` → `packages/*`.

**Daily work:**

- Edit package code in the central monorepo → live in the project **instantly** through the symlink chain (no Composer run needed).
- `moox:devstatus` shows the link status.
- Work in the monorepo with the usual branches + PRs.

> **Updating `composer.lock`:** When a package's **dependencies** change (not just its code), run `composer update "moox/*"` locally and commit the updated `composer.lock`. That lock is the reproducible base the production server uses via `composer install`.

> **Do not run a "deploy" rewrite** (e.g. `moox:deploy`) in this project — it would remove the `packages/` symlinks and reset `composer.json` to the Packagist-clean version, which can't resolve these packages.

---

## Setup B — CI (GitHub Actions)

CI has no central monorepo. A **sparse checkout** fetches only the needed package folders as real copies in `packages/`.

### Monorepo access (GitHub deploy key)

CI uses a GitHub **deploy key** — but the **private** half must be available at runtime as a secret, since an Actions runner has no persistent SSH identity. A deploy key is an SSH **keypair** whose halves go to two places:

1. Generate the keypair (no passphrase — see note): `ssh-keygen -t ed25519 -C "ci-consuming-project" -f ci_monorepo_key -N ""`
2. **Public** key (`ci_monorepo_key.pub`) → **monorepo** → Settings → **Deploy keys** → *Add deploy key* (read-only).
3. **Private** key (`ci_monorepo_key`) → **consuming project** → Settings → Secrets → Actions → e.g. `MOOX_DEPLOY_MONOREPO_PACKAGES`.

> **Leave the passphrase empty** (`-N ""`): CI runs unattended — a passphrase would produce an interactive prompt nobody answers. Protection comes from the storage location (encrypted secret) plus read-only scope and individual rotation, not the passphrase. **Personal** SSH keys (laptops) do get a passphrase.

> A public key can be registered as a deploy key on **one** repo only. CI, and each server, each need their own keypair.

### Reusable step: composite action

The sparse checkout is needed by multiple workflows. Wrap it in a local **composite action** — a folder with `action.yml` that each workflow calls in one line.

`.github/actions/fetch-moox-packages/action.yml`:

```yaml
name: Fetch Moox Packages
description: Fetch the required Moox packages from the monorepo into packages/ via sparse checkout
inputs:
  ssh-key:
    description: Private deploy key for the monorepo
    required: true
  packages:
    description: Space-separated list of monorepo folder names
    required: true
runs:
  using: composite
  steps:
    - name: Load SSH key for the monorepo
      uses: webfactory/ssh-agent@v0.9.0
      with:
        ssh-private-key: ${{ inputs.ssh-key }}
    - name: Fetch required packages into packages/
      shell: bash
      run: |
        mkdir -p packages
        git clone --filter=blob:none --sparse --depth 1 -b main \
          git@github.com:mooxphp/moox.git .moox-src
        ( cd .moox-src && git sparse-checkout set $(for p in ${{ inputs.packages }}; do echo "packages/$p"; done) )
        # sync per Moox folder - your own packages in packages/ stay untouched
        for p in ${{ inputs.packages }}; do
          rsync -a --delete ".moox-src/packages/$p/" "packages/$p/"
        done
        rm -rf .moox-src
```

> **No `rm -rf packages`** (the whole directory): it syncs **per Moox folder** via `rsync -a --delete`. Your own packages in `packages/` are preserved, and files deleted in the monorepo disappear here too (no leftovers). The same `rsync` logic is used in the deploy script — consistent across environments.

### The resolve workflow

> **Avoid a name collision:** if a workflow with the same name already exists in the repo (or another workflow chains to this one via `workflow_run`), give this one a distinct `name:` and file so the trigger stays unambiguous.

`.github/workflows/resolve-moox.yml`:

```yaml
name: Resolve Moox Packages

on:
  push:
    branches: [dev, main]
  pull_request:

jobs:
  resolve:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout project
        uses: actions/checkout@v4

      - name: Load package list
        id: pkgs
        run: echo "list=$(cat .github/moox-packages.txt)" >> "$GITHUB_OUTPUT"

      - name: Fetch Moox packages
        uses: ./.github/actions/fetch-moox-packages
        with:
          ssh-key: ${{ secrets.MOOX_DEPLOY_MONOREPO_PACKAGES }}
          packages: ${{ steps.pkgs.outputs.list }}

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          coverage: none

      - name: Composer cache
        uses: actions/cache@v4
        with:
          path: ~/.composer/cache
          key: composer-${{ runner.os }}-${{ github.run_id }}
          restore-keys: composer-${{ runner.os }}-

      - name: Resolve Moox packages fresh
        run: composer update "moox/*" --no-interaction --prefer-dist --no-progress --no-scripts
```

> **`--no-scripts` matters here:** without it, the `post-autoload-dump` scripts (`package:discover`, `filament:upgrade`) **boot the app** — and a booting app needs `.env`, storage directories, an app key **and a database** (some packages query the DB on boot, e.g. a `scopes` table). Since this workflow only verifies that packages **resolve**, `--no-scripts` skips the boot entirely — no DB/env setup needed. Full boot + tests (with a database + migrations) belong in a separate test workflow.

> **Cache key:** `github.run_id` is unique per run, so a fresh cache is always **written**, while `restore-keys` loads the last matching one on start. This avoids tying the cache to `composer.lock` (which `composer update` doesn't necessarily change beforehand) while still being useful.

### Test workflow

Tests can only run when the packages are present in `packages/`, otherwise `composer` fails before any test starts. So a test workflow must call the same composite action **before** Composer/tests. Unlike the resolve workflow, here the app actually boots (migrations, tests), so it needs a database:

```yaml
name: Tests

on:
  workflow_run:
    workflows: ['Resolve Moox Packages']   # must match the resolve workflow's name:
    types: [completed]
    branches:
      - 'main'
      - 'feature/**'

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          ref: ${{ github.event.workflow_run.head_branch }}

      - name: Load package list
        id: pkgs
        run: echo "list=$(cat .github/moox-packages.txt)" >> "$GITHUB_OUTPUT"

      - name: Fetch Moox packages
        uses: ./.github/actions/fetch-moox-packages
        with:
          ssh-key: ${{ secrets.MOOX_DEPLOY_MONOREPO_PACKAGES }}
          packages: ${{ steps.pkgs.outputs.list }}

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, mysql, pdo_mysql
          coverage: none

      - name: Install dependencies
        run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist

      - name: Setup MySQL
        run: |
          sudo /etc/init.d/mysql start
          mysql -e 'CREATE DATABASE IF NOT EXISTS laravel;' -uroot -proot

      - name: Run migrations
        env:
          APP_ENV: testing
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: laravel
          DB_USERNAME: root
          DB_PASSWORD: root
        run: php artisan migrate

      - name: Execute tests
        env:
          APP_ENV: testing
          APP_KEY: base64:CHANGE_ME_TO_A_TEST_KEY
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: laravel
          DB_USERNAME: root
          DB_PASSWORD: root
        run: vendor/bin/pest --ci
```

> **`workflow_run` + default branch:** a `workflow_run`-triggered workflow only fires if its file exists on the **default branch** (usually `main`). The `branches` list then filters on the branch the *triggering* run was on. So to test on `dev`, the workflow file must reach `main`, not just live on `dev`.

---

## Setup C — Server deployment (tested on Laravel Forge)

Typical setup: a staging server (branch `dev`) and a production server (branch `main`), each auto-deploying on push.

### Deploy key (one dedicated key per server)

A Forge server already has a key (`/home/forge/.ssh/id_rsa.pub`), but it's already registered as the deploy key for the **site repo**. GitHub allows a public key as a deploy key on **one** repo only, so a second entry for the monorepo fails with **"Key is already in use."** Use a dedicated key:

1. As the `forge` user: `ssh-keygen -t ed25519 -C "forge-monorepo-<server>" -f ~/.ssh/monorepo -N ""`
2. `cat ~/.ssh/monorepo.pub` → **monorepo** → Deploy keys (read-only).
3. One dedicated key per server.

**Git must use this key specifically for the monorepo clone.** There may be no central `~/.ssh/config` (Forge passes per-site keys explicitly via managed `forge-config-*` files — don't touch those). The robust approach is to point at the key **inline** in the clone command via `GIT_SSH_COMMAND`, which works regardless of the surrounding SSH config:

```bash
GIT_SSH_COMMAND="ssh -i /home/forge/.ssh/monorepo -o IdentitiesOnly=yes" \
  git clone ... git@github.com:mooxphp/moox.git .moox-src
```

`-o IdentitiesOnly=yes` offers only this key (prevents "too many authentication failures" when an agent tries other keys first). Test as the `forge` user before the first deploy:

```bash
GIT_SSH_COMMAND="ssh -i /home/forge/.ssh/monorepo -o IdentitiesOnly=yes" \
  git clone --depth 1 git@github.com:mooxphp/moox.git /tmp/moox-test && echo OK && rm -rf /tmp/moox-test
```

### Deploy script (zero-downtime)

The original deploy script stays unchanged — only the sparse-checkout block is **inserted**, after the release checkout and before `composer install`. On Forge's zero-downtime deployments it belongs right after `cd $FORGE_RELEASE_DIRECTORY` (the fresh release directory holding your committed own packages but not the gitignored monorepo folders):

```bash
$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY

# --- fetch Moox packages from the monorepo into packages/ ---
# IMPORTANT: no "rm -rf packages" - that would delete your own (committed) packages!
PACKAGES="$(cat .github/moox-packages.txt)"
mkdir -p packages
GIT_SSH_COMMAND="ssh -i /home/forge/.ssh/monorepo -o IdentitiesOnly=yes" \
  git clone --filter=blob:none --sparse --depth 1 -b main git@github.com:mooxphp/moox.git .moox-src
( cd .moox-src && git sparse-checkout set $(for p in $PACKAGES; do echo "packages/$p"; done) )
for p in $PACKAGES; do
  rsync -a --delete ".moox-src/packages/$p/" "packages/$p/"
done
rm -rf .moox-src
# -----------------------------------------------------------

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
$FORGE_PHP artisan optimize
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan migrate --force

npm ci || npm install
npm run build

$ACTIVATE_RELEASE()

$RESTART_QUEUES()
```

> **Order matters:** the sparse checkout sits right after `cd $FORGE_RELEASE_DIRECTORY`. If it ran earlier, the packages would land in the wrong directory and `composer install` in the release wouldn't find them.

> **`--no-dev`:** make sure all **consumed** packages are in the root `require` (not `require-dev`), or they're missing under `--no-dev`. Only dev-only tooling (e.g. `moox/devlink`) belongs in `require-dev`.

> **`install` is sufficient:** with path packages in copy mode the package **code** always comes from the freshly `rsync`'d `packages/` files (Composer doesn't re-download path packages), so the current monorepo state is present on every deploy regardless of `install` vs. `update`. The only difference is **re-resolution**: `install` follows `composer.lock` and would miss a **new sub-dependency** a changed package newly declares; `update "moox/*"` catches that. If that ever bites, switch the staging server's one Composer line to `$FORGE_COMPOSER update "moox/*" --no-dev ...`.

> **Always-current `main`:** the sparse checkout pulls the current monorepo `main` every time. If production later needs pinned, controlled states, switch to a pinned commit/tag there (`-b <tag>` or `--depth 1 <sha>`).

The production server's script is identical (branch `main`).

---

## Deploy hook: monorepo change → staging update *(optional)*

> **Moox does not have this implemented.** Staging is updated by pushing to the project branch (or re-deploying manually in Forge). The steps below are optional — use them if you want monorepo pushes to trigger staging automatically.

Forge's auto-deploy only triggers on a push to the **project branch** — not on monorepo changes. Without a hook, staging sits still until someone pushes to the project. A deploy hook flips that:

```
Push to monorepo main
  → GitHub Action in the monorepo
  → curl the staging server's Forge deploy-hook URL
  → staging deploy script runs → sparse checkout pulls the new state
  → staging is current
```

1. **Forge** → staging site → copy the deploy-trigger URL.
2. **Monorepo** → Settings → Secrets → Actions → e.g. `FORGE_DEPLOY_HOOK_STAGING`.
3. Workflow in the **monorepo**:

```yaml
name: Notify Consumers
on:
  push:
    branches: [main]
jobs:
  trigger-staging:
    runs-on: ubuntu-latest
    steps:
      - name: Redeploy consumer staging
        run: curl -fsSL "${{ secrets.FORGE_DEPLOY_HOOK_STAGING }}"
```

> If you set this up: trigger **staging only**, not production (keep production state deliberate). The hook URL is a secret. With many consumers, the monorepo workflow would have to ping many hooks — see "When to reconsider".

---

## Daily workflow

```
1. Change a package in the monorepo
   → instantly testable locally via the devlink symlink
   → in the monorepo: feature branch, commit, push, PR → merge to main

2. Merge to monorepo main
   → (optional) a monorepo Action pings the staging deploy hook → server re-deploys
   → otherwise re-deploy staging manually or push to the project branch
   → sparse checkout pulls the new monorepo state on deploy

3. Update production (deliberately)
   → push to the project's main branch → production server deploys
```

### Where commits, branches and PRs go

- **Package code:** in the monorepo, normal branch/PR workflow. A protected `main` means the deployed state is reviewed.
- **Project code:** in the consuming project's repo (`dev`/`main`).
- **`packages/`:** symlinks locally, copies on server/CI — the monorepo folders are gitignored, your own packages are committed.
- **`vendor/`:** symlinks into `packages/`, not git.

### Changing packages — the right place

**Rule of thumb: work on a package always in the monorepo, never in `packages/` or `vendor/`.**

| Where you change it | What happens |
|---|---|
| In `vendor/...` or `packages/...` (server) | **Ephemeral** — overwritten on the next deploy |
| In the central monorepo locally (devlink symlink) | Instantly visible in the project, ideal for development |
| In the monorepo + commit/push to main | Staging re-deploys automatically via the hook |

---

## When to reconsider

This approach is optimal for **few consumers** with active monorepo development. Reconsider when:

- The package list drifts across `config/devlink.php`, the list file and `composer.json` → the list file is already generated from `config/devlink.php` (`moox:devlink-export`); the next step would be to also generate the `composer.json` `repositories` entries, making `config/devlink.php` the only source.
- You have **many consumers** → the monorepo notify workflow would have to ping many hooks, and "always-current main everywhere" gets hard to control.
- **Stability matters more than bleeding edge** → then the excluded path becomes attractive: subtree-split on merge (branch push, no tag) → private Packagist / Satis → normal `composer install`.

---

## Troubleshooting

**Resolution fails: packages are `dev-dev` but `dev-main` is required**
> `moox/category dev-dev requires moox/core dev-main -> ... moox/core[dev-dev] from path repo ... do not match your constraint`

A path entry is missing the `versions` option. Add `"versions": { "moox/<name>": "dev-main" }` to each affected entry (`core` first — almost everything requires it), then `composer update "moox/*"`. Typically happens when a newly devlinked package has no `versions` yet.

**`Please provide a valid cache path` or `database ... does not exist` during the composer step**
The app boots (via `post-autoload-dump`) and needs `.env`/storage/DB. In the resolve workflow, `--no-scripts` prevents the boot — no DB setup needed. Where the app *should* boot (test workflow, server), provide a database and run `migrate` first.

**`Class ... not found` / `Unable to locate a component` during deploy**
A code reference points at a package that's missing from `composer.json` (e.g. a package that was removed but is still referenced in code). Find it (`grep -rn "<Namespace>" app/ resources/ packages/`). If still needed → add it back; if not → remove the reference.

**`Unable to prepare route [...] Another route has already been assigned name [...]`**
Two routes share the same `->name(...)`. `route:cache` (part of `optimize`) surfaces it; without caching it's invisible locally. Find the duplicate (`grep -rn "<the-name>"`) and make one unique.

**Composer can't find a package**
Is the folder under `packages/`? Does the `require` name match the package `composer.json`'s `name` field? Does the list contain the correct **folder name** (not package name)? Try `composer clear-cache && composer install`.

**`packages/` is empty on the server**
Does the sparse-checkout block run **before** `composer install`? Is the deploy key registered and does `GIT_SSH_COMMAND` point at it? Run the clone test above as the `forge` user.

**Own (non-monorepo) packages gone after deploy**
The deploy script / composite action must **not** contain `rm -rf packages` (the whole directory) — only per-folder `rsync --delete`. And the `.gitignore` exception (`!/packages/<name>/`) must be present so they're versioned.

**Staging shows the old state despite a monorepo fix**
Re-deploy staging manually (Moox has no monorepo deploy hook). If you added one, confirm the Action is active. `rsync --delete` (not `cp`) ensures files deleted in the monorepo also disappear on the server.

**Server: trouble with vendor symlinks**
Set `COMPOSER_MIRROR_PATH_REPOS=1` before `composer install` → copies into `vendor/` instead of symlinking.

**Local symlinks missing**
Did `php artisan moox:devlink` run? `moox:devstatus` shows the status.

---

## Summary

| Environment | `packages/` | Composer command |
|---|---|---|
| **Local** | `moox:devlink` symlinks configured packages | `composer update "moox/*"` (on dep changes, commit the lock) |
| **CI (resolve)** | sparse checkout copies needed folders | `composer update "moox/*" --no-scripts` (verify resolution) |
| **Server (staging)** | sparse checkout copies needed folders | `composer install` (or `update "moox/*"` for freshness) |
| **Server (production)** | sparse checkout copies needed folders | `composer install` (locked, reproducible) |

| Building block | Job |
|---|---|
| `path` entries with `versions: dev-main` | Composer resolves the packages from `packages/` as `dev-main` |
| All `moox/*` on `*` + `minimum-stability: dev` | monorepo state wins (path repos are canonical); only third-party from Packagist |
| `moox:devlink` (local) | symlinks the configured packages |
| sparse checkout (CI/server) | fetches **only** needed folders, never the whole monorepo |
| `moox:devlink-export` | single source for the package list, from `config/devlink.php` |
| deploy hook (monorepo → staging) *(optional, not in Moox)* | a monorepo push can update staging automatically |

**Core principle:** all consumed `moox/*` packages end up under `packages/` — locally as devlink symlinks, on CI/servers as sparse-checkout copies — and thus come from the monorepo state instead of Packagist. The full monorepo (apps, tooling) is never cloned, only the consumed package folders. One committed `composer.json` (`path` entries with `versions: dev-main`, all `moox/*` on `*`, `minimum-stability: dev` + `prefer-stable: true`) serves all environments. No split, no release, no Packagist. Work always happens in the monorepo.