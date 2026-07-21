# Changelog

## Unreleased

- Defence-in-depth runtime integrity ([#12](https://github.com/mooxphp/kosit-validator/issues/12)): `validate()` checksum-verifies the XRechnung bundle and extracts it to a private temp directory before use; the validator JAR is hashed in memory and executed from a private temp copy to narrow verify→execute TOCTOU. Install now stores `{xrechnung_dir}/.xrechnung-bundle.zip`.
- Clarify SHA-256 checksum failure messages for install vs runtime validation contexts ([#11](https://github.com/mooxphp/kosit-validator/issues/11)).

We currently don't track other changes in this package. Please refer to the [Moox Monorepo](https://github.com/mooxphp/moox) for the latest changes.
