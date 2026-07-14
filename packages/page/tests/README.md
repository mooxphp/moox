# Page Package Tests

Diese Tests gehoeren ausschliesslich zum Package `moox/page`.

## Workflow (Test-First)

1. **Vor jeder Aenderung:** Tests ausfuehren — alle muessen gruen sein.
2. **Aenderung implementieren** (nur im Package-Scope `moox/packages/page`).
3. **Nach der Aenderung:** Tests erneut ausfuehren.

## Ausfuehrung

Aus der Host-App (`heco/web`):

```bash
php vendor/bin/pest --configuration=packages/page/phpunit.xml.dist
```

Architektur-Tests separat:

```bash
php vendor/bin/pest --configuration=packages/page/phpunit.xml.dist --bootstrap=packages/page/tests/bootstrap-arch.php packages/page/tests/ArchTest.php
```

Aus dem Moox-Monorepo (mit `vendor/` im Root):

```bash
cd packages/page && composer test
```

Einzelne Datei:

```bash
php vendor/bin/pest --configuration=packages/page/phpunit.xml.dist packages/page/tests/Feature/PageControllerTest.php
```

## Abdeckung (39 Tests)

| Bereich | Testdatei |
|---------|-----------|
| PageController, Homepage, Layouts | `Feature/PageControllerTest.php` |
| Response-Cache & Invalidierung | `Feature/PageCacheTest.php` |
| Filament ListPages | `Feature/Filament/FilamentPageTest.php` |
| PublishedPageQuery | `Unit/PublishedPageQueryTest.php` |
| PageLocaleResolver | `Unit/PageLocaleResolverTest.php` |
| PageResponseCache | `Unit/PageResponseCacheTest.php` |
| PagePermalink | `Unit/PagePermalinkTest.php` |
| Page-Model & Homepage-Regeln | `Unit/PageModelTest.php` |

## Technik

- Dual-Mode `TestCase`: Host-App (`Tests\TestCase`) oder Orchestra Testbench
- Jede Testdatei bindet via `pest()->extend(TestCase::class)` die Laravel-Umgebung
- Schema-Setup in `Concerns/CreatesPageSchema.php`
- Stub-Views unter `tests/stubs/views/`
