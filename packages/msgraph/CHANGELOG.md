# Changelog

## 1.0.0 — 2026-08-19

### Added

- `GraphConnection` readonly value object — validates and carries Azure AD credentials
- Connection registry (`Auth\ConnectionRegistry`) — named multi-tenant credential resolution returning `GraphConnection` instances
- Graph client factory (`Auth\GraphClientFactory`) — builds authenticated `GraphServiceClient` instances; immutable-ID middleware is applied unconditionally (even on injected handler stacks)
- Immutable-ID Guzzle middleware — every outgoing request carries `Prefer: IdType="ImmutableId"`
- Typed exception hierarchy for API errors: `GraphException`, `GraphAuthenticationException`, `GraphRateLimitException`, `GraphNotFoundException`, `GraphConnectionException`
- `InvalidConnectionException` (extends `InvalidArgumentException`) for configuration/credential errors — distinct from API errors
- Exception mapper (`Exceptions\ExceptionMapper`) — maps HTTP/transport errors to typed exceptions
- `Mail\` area sub-namespace placeholder for future mail-specific capabilities
- Configuration file with named connections and default connection setting
