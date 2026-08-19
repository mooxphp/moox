# Changelog

## Unreleased

### Added

- `InboxDriver` contract: transport-neutral interface for fetching, claiming and settling inbox messages
- `SettlementOutcome` enum: `Processed`, `Failed`, `Ignored` — semantic outcomes, not folder operations
- `InboxMessageDto`: provider-agnostic message representation
- `MessagePage`: resumable page result with opaque cursor
- `InboxDriverManager`: resolves a named mailbox to its configured driver via configuration strings
- Two-tier config shape: connections (credentials) + mailboxes (driver, connection, address)
- `InMemoryDriver`: in-memory fake driver for testing with no network access
- Pest test suite covering the contract, driver manager, and fake driver

---

We previously didn't track changes in this package. Please refer to the [Moox Monorepo](https://github.com/mooxphp/moox) for historical changes.
