# Security Policy

## Supported Versions

We maintain the current version of `moox/verapdf` actively.

Do not expect security fixes for older versions.

## Reporting a Vulnerability

If you find any security-related bug, please report it to security@moox.org.

Please do not use GitHub issues, to give us enough time to review and fix the issue before others can exploit it.

## Installer supply chain (`verapdf:install`)

The greenfield installer zip is treated as untrusted until verified:

1. **Pinned SHA-256** — `config/verapdf.php` key `installer.sha256` (env: `VERAPDF_INSTALLER_SHA256`) must match the zip bytes at `installer.download_url`. Verification uses `hash_file('sha256')` with `hash_equals`. An empty or malformed pin fails closed. veraPDF also publishes GPG `.asc` signatures on [software.verapdf.org](https://software.verapdf.org/releases/1.30); this package pins the digest in config as the operational integrity check for headless CI/servers without requiring GPG on every host.
2. **Zip-slip rejection** — every ZIP entry is validated before extract; absolute paths and `..` segments are refused (same idea as `VeraPdfOutputPath` subdirectory hardening).
3. **Non-destructive failures** — download and extract run in a system temp staging directory. Checksum or zip-slip failure aborts before IzPack runs and before `--force` deletes an existing `base_path` install.

### Updating the pin when the veraPDF version changes

On a trusted machine:

```bash
URL='https://software.verapdf.org/releases/1.30/verapdf-greenfield-1.30.1-installer.zip'
curl -fsSL -o /tmp/verapdf-installer.zip "$URL"
shasum -a 256 /tmp/verapdf-installer.zip
# or: openssl dgst -sha256 /tmp/verapdf-installer.zip
```

Paste the 64-character hex into `installer.sha256` in the same change that updates `installer.version` and `installer.download_url`. If you override `VERAPDF_DOWNLOAD_URL`, you must also set a matching `VERAPDF_INSTALLER_SHA256`.
