# Changelog

## Unreleased

### Changed

- Reduce cyclomatic complexity of `KositInstaller::install` by extracting
  backup, promote, rollback, and discard phases ([#16](https://github.com/mooxphp/kosit-validator/issues/16)).
  Checksum verification still runs in staging before any artefact is moved into
  its final location; behaviour is unchanged.

### Fixed

- SonarQube line-length (120 cols) findings in installer checksum messages, default
  download URL test literals, `KositService::jarPath()`, and
  `InstallKositCommandTest` ([#15](https://github.com/mooxphp/kosit-validator/issues/15)).
  Long lines were wrapped or extracted into named constants/locals.
- Pint cannot enforce max line length (PHP-CS-Fixer has no such fixer), so
  120-col wraps stay manual.

- Slim down the default test harness ([#14](https://github.com/mooxphp/kosit-validator/issues/14)): `tests/TestCase.php` now only registers `CoreServiceProvider` + `KositValidatorServiceProvider` and the config Unit/most Feature tests need; the Filament admin panel, Livewire, and icon providers moved to an opt-in `tests/FilamentTestCase.php` used by `Feature/KositValidationResourceTest.php` and `Feature/KositReportControllerTest.php`.
- Defence-in-depth runtime integrity ([#12](https://github.com/mooxphp/kosit-validator/issues/12)): `validate()` checksum-verifies the XRechnung bundle and extracts it to a private temp directory before use; the validator JAR is hashed in memory and executed from a private temp copy to narrow verify→execute TOCTOU. Install now stores `{xrechnung_dir}/.xrechnung-bundle.zip`.
- Clarify SHA-256 checksum failure messages for install vs runtime validation contexts ([#11](https://github.com/mooxphp/kosit-validator/issues/11)).

We currently don't track other changes in this package. Please refer to the [Moox Monorepo](https://github.com/mooxphp/moox) for the latest changes.
