# Packages

## Repos


| Repo  | Notes                                  |
| ----- | -------------------------------------- |
| moox  | Main monorepo (branch example: `bpmn`) |
| pro   |                                        |
| web   |                                        |
| intra |                                        |


## Stability

Describes **package maturity in the repo** (not `composer.json`): empty, docs-only, half-finished, outdated, or no longer worth using.


| Value        | Meaning (in this registry)                                                                                    |
| ------------ | ------------------------------------------------------------------------------------------------------------- |
| stable       | Production-ready or intended for everyday use.                                                                |
| beta         | Half-finished or open gaps/follow-ups, but recognizable and usable as a package (with caveats).               |
| alpha        | Very early; basics only; breakage is common.                                                                  |
| experimental | Deliberately for trying things out; API/behavior may change significantly.                                    |
| stale        | Barely maintained or outdated in approach, but still present.                                                 |
| idea         | Empty, placeholder, concept/docs folder only, or not a real package yet.                                      |
| obsolete     | No longer sensible to use (replaced, abandoned, or clearly deprecated).                                         |


## Scopes and other features


| Value |
| ----- |
| Yes   |
| No    |
| Todo  |


## Registry

All rows use the Composer-style name `moox/…`. Includes packages with `composer.json` and folders under `packages/` without it yet. Sorted alphabetically.

**Scope** (Moox scope system — see `packages/core/docs/scope-system-team-guide.md`): **Yes** / **No** / **Todo** = whether the scope model fits this package.

**Stability** = see **Stability** above (maturity / empty / docs-only / half-finished / no longer useful).


| Package                | Repo (Branch) | Stability    | Scope | … more features |
| ---------------------- | ------------- | ------------ | ----- | --------------- |
| moox/audit             | moox          | Stable       | Todo  | ...             |
| moox/backup            | moox          | Idea         | Todo  | ...             |
| moox/backup-server     | moox          | Stable       | No    | ...             |
| moox/block-editor      | moox          | Beta         | Yes   | ...             |
| moox/bpmn              | moox          | Stable       | Todo  | ...             |
| moox/brand             | moox          | Idea         | Todo  | ...             |
| moox/build             | moox          | Beta         | No    | ...             |
| moox/calendar          | moox          | Idea         | Yes   | ...             |
| moox/cart              | moox          | Idea         | Yes   | ...             |
| moox/category          | moox          | Stable       | Yes   | ...             |
| moox/clipboard         | moox          | Stable       | No    | ...             |
| moox/components        | moox          | Beta         | No    | ...             |
| moox/core              | moox          | Stable       | No    | ...             |
| moox/customer          | moox          | Idea         | Yes   | ...             |
| moox/data-legacy       | moox          | Stable       | Todo  | ...             |
| moox/demo              | moox          | Idea         | No    | ...             |
| moox/devlink           | moox          | Stable       | No    | ...             |
| moox/devops            | moox          | Stable       | No    | ...             |
| moox/devtools          | moox          | Stable       | No    | ...             |
| moox/docs              | moox          | Idea         | No    | ...             |
| moox/draft             | moox          | Stable       | Yes   | ...             |
| moox/e-billing  | moox          | Idea         | No    | ...             |
| moox/expiry            | moox          | Stable       | Todo  | ...             |
| moox/featherlight      | moox          | Stable       | No    | ...             |
| moox/file-icons        | moox          | Stable       | No    | ...             |
| moox/firewall          | moox          | Stable       | Todo  | ...             |
| moox/flag-icons-circle | moox          | Stable       | No    | ...             |
| moox/flag-icons-origin | moox          | Stable       | No    | ...             |
| moox/flag-icons-rect   | moox          | Stable       | No    | ...             |
| moox/flag-icons-square | moox          | Stable       | No    | ...             |
| moox/forge             | moox          | Idea         | Todo  | ...             |
| moox/forms             | moox          | Idea         | Todo  | ...             |
| moox/frontend          | moox          | Stable       | No    | ...             |
| moox/github            | moox          | Stable       | No    | ...             |
| moox/impersonate       | moox          | Idea         | Todo  | ...             |
| moox/invoice           | moox          | Idea         | Todo   | ...             |
| moox/item              | moox          | Stable       | Yes   | ...             |
| moox/jobs              | moox          | Stable       | No    | ...             |
| moox/json              | moox          | Idea         | Todo  | ...             |
| moox/kosit-validator   | moox          | Idea         | No    | ...             |
| moox/laravel-icons     | moox          | Stable       | No    | ...             |
| moox/localization      | moox          | Stable       | No    | ...             |
| moox/login-link        | moox          | Stable       | No    | ...             |
| moox/mail-inbox        | moox          | Idea         | Todo  | ...             |
| moox/markdown          | moox          | Idea         | Todo  | ...             |
| moox/media             | moox          | Stable       | Yes   | ...             |
| moox/module            | moox          | Idea         | Todo  | ...             |
| moox/monorepo          | moox          | Stable       | No    | ...             |
| moox/news              | moox          | Beta         | Yes   | ...             |
| moox/notifications     | moox          | Stable       | Todo  | ...             |
| moox/packages          | moox          | Stable       | Todo  | ...             |
| moox/packagist         | moox          | Idea         | Todo  | ...             |
| moox/page              | moox          | Stable       | Yes   | ...             |
| moox/passkey           | moox          | Stable       | Yes   | ...             |
| moox/pdf-parser        | moox          | Idea         | No    | ...             |
| moox/permission        | moox          | Idea         | Todo  | ...             |
| moox/post              | moox          | Idea         | Yes   | ...             |
| moox/press             | moox          | Beta         | Yes   | ...             |
| moox/press-trainings   | moox          | Idea         | Yes   | ...             |
| moox/press-wiki        | moox          | Idea         | Yes   | ...             |
| moox/product           | moox          | Idea         | Yes   | ...             |
| moox/progress          | moox          | Stable       | No    | ...             |
| moox/prompts           | moox          | Experimental | Todo  | ...             |
| moox/record            | moox          | Stable       | Yes   | ...             |
| moox/redis             | moox          | Idea         | No    | ...             |
| moox/restore           | moox          | Stable       | No    | ...             |
| moox/schedule          | moox          | Idea         | Todo  | ...             |
| moox/scopes            | moox          | Experimental | Yes   | ...             |
| moox/security          | moox          | Stable       | Todo  | ...             |
| moox/seo               | moox          | Idea         | Todo  | ...             |
| moox/settings          | moox          | Idea         | Todo  | ...             |
| moox/skeleton          | moox          | Stale        | No    | ...             |
| moox/slug              | moox          | Stable       | Yes   | ...             |
| moox/tag               | moox          | Stable       | Yes   | ...             |
| moox/themes            | moox          | Idea         | No    | ...             |
| moox/trainings         | moox          | Idea         | Yes   | ...             |
| moox/tree              | moox          | Idea         | Yes   | ...             |
| moox/user              | moox          | Stable       | Yes   | ...             |
| moox/user-device       | moox          | Stable       | Yes   | ...             |
| moox/user-session      | moox          | Obsolete     | Yes   | ...             |
| moox/vscode            | moox          | Stale        | No    | ...             |
| moox/wishlist          | moox          | Idea         | Yes   | ...             |
| moox/wp-install        | moox          | Stale        | No    | ...             |
| moox/zugferd           | moox          | Idea         | No    | ...             |


