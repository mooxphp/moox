# Moox Click Dummy

Serve auth-protected HTML clickdummies (and sibling frontend assets) from a configurable storage root.

## Usage

1. Drop HTML and assets under the configured path (default: `storage/clickdummy` in the host app).
2. Open `/clickdummy/...` while logged in as an admin (protected by `moox/frontend-auth`).
3. Directory URLs resolve to `index.html` when present.

## Tests

From the consuming Laravel app:

```bash
php artisan test --compact packages/click-dummy/tests/Feature/ClickDummyTest.php
```
