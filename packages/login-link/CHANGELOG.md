# Changelog

All notable changes to `moox/login-link` will be documented in this file.

## Unreleased

### Changed

- Used/expired/invalid consume uses the packaged HTML demo by default (no host theme). A process handler may implement `RendersUnavailablePage` to replace that page. Preview: `/login-link/examples/unavailable/{expired|used|invalid}`.
- `ProcessLinkMail` looks up `moox/mail-template` by process `template_key` only when that package is present (`class_exists`, no composer dependency). MJML vs HTML is decided in mail-template. Without a matching row, one packaged HTML demo (`login-link::mail.process-link`) is sent.
- Removed `login-link.templates` view map, `mail_template_key`, and packaged MJML / per-process mail layouts.

### Added

- Packaged English examples: `login` (passwordless panel sign-in), `verify-email` (mailbox confirmation), `mass-mail` (campaign confirmation, invalidate prior off). One HTML demo mail when no MailTemplate row matches. Issue public examples with `php artisan login-link:example`.
- Public consume distinguishes used / expired / invalid and renders the packaged HTML demo. Support contact comes from `login-link.public_support`.
- Nullable `panel_id` on link instances; auth issue requires panel id, public forces null.
- JSON `payload` on link instances (call context; subject remains identity).
- Process `invalidate_prior` policy (default true; mass/tracking can disable).
- Built-in non-login `ack` handler + `ProcessLinkAcknowledged` event for proving signed-link redemption without authentication.
- Redemption resolves the handler via the process definition's `handler_key` (slug may differ).
- Lifecycle scoped to process + subject: invalidate prior valid links, rate limits, and resend.
- `ProcessLinkMail` uses process `mail_from` / `content` / expiry; missing MailTemplate rows use the packaged HTML demo.
- Resend action on Login Link instances.
- `LoginLinkProcess` (`BaseRecordModel`) + Filament `BaseRecordResource` for process definitions (`title`, `slug`, `mail_from`, `content`, `handler_key`, `expiry_minutes`) with List/Create/View/Edit pages.
- Handler key validated against `RedemptionHandlerRegistry` (unregistered keys rejected).
- `LoginLinkProcessSeeder` seeds the built-in `login` process; wired via `extra.moox.install.seed`.
- Nullable polymorphic `subject` on `login_links` beside the legacy `user` morph; login populates both.
- First-class `process` discriminator on `login_links` (replaces the soft `expiry_job` concept); defaults to `login`.
- Additive migration stub `add_subject_and_process_to_login_links_table`.
- `RedemptionHandlerRegistry` aggregates process handlers from package config (`{package}.login-link.handlers`) and `login-link.handlers` (scopes-style).
- `LoginRedemptionHandler` as the built-in `login` handler; authenticates via the panel's configured guard/model.
- `Moox\LoginLink\Plugins\LoginLinkPlugin` under `src/Plugins/` (Moox package convention).
- `PanelLoginEnhancer` extends the panel's configured login class with the magic-link hint (no fixed login page replacement).
- Configurable send rate limiting (`login-link.rate_limit.send`).
- Package tests (`LoginLinkRateLimiter`, `LoginLinkRedemptionService`, `RedemptionHandlerRegistry`, `PanelLoginEnhancer`).

### Changed

- `LoginLinkRedemptionService` dispatches to the registered handler for the link's process (defaults to `login` until the process column lands).
- `LoginLinkServiceProvider` extends `Moox\Core\MooxServiceProvider`.
- README documents signed redemption URL, queue requirement, and integration steps.

### Removed

- Demo dump flow (`dump` handler/template, `demo-dump` / `demo-campaign` processes, `php artisan login-link:demo`, `/login-link/demo/dump`).
- Seeded `ack` process definition (handler remains for tests and custom processes).
- Legacy optional login page classes `Filament/Pages/Auth/Login` and `LoginWithMooxUser` (superseded by `PanelLoginEnhancer`).
