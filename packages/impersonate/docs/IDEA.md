# Impersonate

Thin Moox layer around [stechstudio/filament-impersonate](https://github.com/stechstudio/filament-impersonate).

- Soft-depends on **moox/audit** for enter/leave activity logs and attributing causer to the real admin
- Does not depend on panel-specific packages (finance, portal, user, …)
- Consumers soft-check `class_exists(Moox\Impersonate\Filament\Actions\ImpersonateAction::class)` before adding Filament actions
