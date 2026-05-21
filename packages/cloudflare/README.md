# Moox Cloudflare

Optional extension for [moox/cache](https://github.com/mooxphp/moox) with Cloudflare zone cache purge targets and API client.

## Installation

```bash
composer require moox/cloudflare
```

## Configuration

```env
CLOUDFLARE_CACHE_ENABLED=true
CLOUDFLARE_API_TOKEN=your-api-token
CLOUDFLARE_ZONE_ID=your-zone-id
CLOUDFLARE_ALLOWED_DOMAINS=example.com,www.example.com
```

## Panel plugins

```php
->plugins([
    Moox\Cache\Plugins\CachePlugin::make(),
    Moox\Cloudflare\CloudflareCachePlugin::make(),
])
```

## Targets

- `cloudflare-purge-all`
- `cloudflare-purge-files`
- `cloudflare-purge-tags`
- `cloudflare-purge-hosts`
