# Changelog

All notable changes to `moox/login-link` will be documented in this file.

## Unreleased

### Changed

- `ProcessLinkMail` compiles MJML with Spatie when present and prefers a matching `moox/mail-template` MailTemplate (process `template_key` / slug, then `login-link.mail_template_key` for the login process only). HTML process templates are unchanged. `LoginLinkEmail` delegates to `ProcessLinkMail`.
- `moox/mail-template` and `moox/mjml` are no longer hard dependencies of `login-link`.

### Added

- Packaged English examples: `login` (passwordless panel sign-in), `verify-email` (mailbox confirmation), `mass-mail` (campaign confirmation, invalidate prior off). Each has its own mail template. Issue public examples with `php artisan login-link:example`.
- Public consume distinguishes used / expired / invalid and renders `login-link::public.unavailable` (override via `login-link.public_unavailable_view`) instead of redirecting to `/`. Support contact comes from `login-link.public_support`.
- Nullable `panel_id` on link instances; auth issue requires panel id, public forces null.
- JSON `payload` on link instances (call context; subject remains identity).
- Process `template_key` resolved via `login-link.templates` config (domain-agnostic).
- Process `invalidate_prior` policy (default true; mass/tracking can disable).
- Built-in non-login `ack` handler + `ProcessLinkAcknowledged` event for proving signed-link redemption without authentication.
- Redemption resolves the handler via the process definition's `handler_key` (slug may differ).
- Lifecycle scoped to process + subject: invalidate prior valid links, rate limits, and resend.
- `ProcessLinkMail` interim mailable uses process `mail_from` / `content` / expiry (falls back to login blade when content empty).
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
- Invalid/expired link feedback on the login page via session flash.
- Package tests (`LoginLinkRateLimiter`, `LoginLinkRedemptionService`, `RedemptionHandlerRegistry`, `PanelLoginEnhancer`).

### Changed

- `LoginLinkRedemptionService` dispatches to the registered handler for the link's process (defaults to `login` until the process column lands).
- `LoginLinkServiceProvider` extends `Moox\Core\MooxServiceProvider`.
- README documents signed redemption URL, queue requirement, and integration steps.

### Removed

- Demo dump flow (`dump` handler/template, `demo-dump` / `demo-campaign` processes, `php artisan login-link:demo`, `/login-link/demo/dump`).
- Seeded `ack` process definition (handler remains for tests and custom processes).
- Legacy optional login page classes `Filament/Pages/Auth/Login` and `LoginWithMooxUser` (superseded by `PanelLoginEnhancer`).
