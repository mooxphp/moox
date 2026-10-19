# Impersonate

Thin Moox layer around [stechstudio/filament-impersonate](https://github.com/stechstudio/filament-impersonate).

- Soft-depends on **moox/audit** for enter/leave activity logs and attributing causer to the real admin
- Does not depend on panel-specific packages (finance, portal, user, …)
- Consumers soft-check `ImpersonateAction::userMay($user, 'portal'|'finance')` (config allowlist + Shield permission)
- **Env (ops):** `MOOX_IMPERSONATE_ENABLED` (default false) and `MOOX_IMPERSONATE_TARGETS` (e.g. `finance,portal`)
- **Shield (who):** fixed permissions `Impersonate:PortalUser` / `Impersonate:FinanceUser` — not env toggles
- With **moox/user-device**, device trust/tracking is skipped while impersonating (documented support behaviour, not a config flag)
