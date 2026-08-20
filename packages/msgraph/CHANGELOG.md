# Changelog

## 1.0.0 — 2026-08-19

### Added

- `GraphConnection` readonly value object — validates and carries Azure AD credentials
- Connection registry (`Auth\ConnectionRegistry`) — named multi-tenant credential resolution returning `GraphConnection` instances
- Graph client factory (`Auth\GraphClientFactory`) — builds authenticated `GraphServiceClient` instances; immutable-ID middleware is applied unconditionally (even on injected handler stacks)
- Immutable-ID Guzzle middleware — every outgoing request carries `Prefer: IdType="ImmutableId"`
- Typed exception hierarchy for API errors: `GraphException`, `GraphAuthenticationException`, `GraphRateLimitException`, `GraphNotFoundException`, `GraphItemNotFoundException`, `GraphMailboxNotFoundException`, `GraphConnectionException`
- `InvalidConnectionException` (extends `InvalidArgumentException`) for configuration/credential errors — distinct from API errors
- Exception mapper (`Exceptions\ExceptionMapper`) — maps HTTP/transport errors to typed exceptions
- `GraphInboxDriver` (`Mail\GraphInboxDriver`) implementing `Moox\MailInbox\Contracts\InboxDriver`: resumable Graph delta fetch with per-run `delta_max_pages_per_poll`, exclusive claim into the processing folder, best-effort settle-by-outcome folder moves, attachment content download
- Mail configuration (`msgraph.mail.folders.*`, `msgraph.mail.page_size`, `msgraph.mail.delta_max_pages_per_poll`) — this package owns folder names; consumers pass outcomes only
- `GraphSyncStateNotFoundException` for expired Graph delta tokens (`syncStateNotFound`)
- `GraphRateLimitException::$retryAfterSeconds` and Retry-After-aware 429 retries (exponential backoff when the header is absent)
- Delta cursor host allowlist before `withUrl()` so a tampered cursor cannot exfiltrate the Graph token
- Configuration file with named connections, default connection setting, and mail folder/page-size settings

### Changed

- `microsoft/microsoft-graph` constraint stays `^2.0`. The driver uses delta/`withUrl`, mail folders and attachments — all present in 2.0 — so narrowing to `^2.26` would exclude consumers without a compatibility reason.
