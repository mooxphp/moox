# Changelog

All notable changes to `moox/login-link` will be documented in this file.

## Unreleased

### Added

- `RedemptionHandlerRegistry` aggregates process handlers from package config (`{package}.login-link.handlers`) and `login-link.handlers` (scopes-style).
- `LoginRedemptionHandler` as the built-in `login` handler; authenticates via the panel's configured guard/model.
- `Moox\LoginLink\Plugins\LoginLinkPlugin` under `src/Plugins/` (Moox package convention).
- `PanelLoginEnhancer` extends the panel's configured login class with the magic-link hint (no fixed login page replacement).
- Configurable send rate limiting (`login-link.rate_limit.send`).
- Invalid/expired link feedback on the login page via session flash.
- Package tests (`LoginLinkRateLimiter`, `LoginLinkRedemptionService`, `RedemptionHandlerRegistry`, `PanelLoginEnhancer`).

### Changed

- `LoginLinkRedemptionService` dispatches to the registered handler for the link's process (defaults to `login` until the process column lands).
- `LoginLinkServiceProvider` extends `Moox\Core\MooxServiceProvider`.
- README documents signed redemption URL, queue requirement, and integration steps.

### Removed

- Legacy optional login page classes `Filament/Pages/Auth/Login` and `LoginWithMooxUser` (superseded by `PanelLoginEnhancer`).
