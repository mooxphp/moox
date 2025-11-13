## Testing in `@item/`

Diese Doku beschreibt, wie die Testumgebung für das Paket `moox/item` gekapselt betrieben werden kann, typische Fehlerursachen und deren Lösungen, sowie den Betrieb in CI inklusive MySQL.

### Überblick

- Das Paket nutzt Orchestra Testbench als gekapselte Laravel-Laufzeit und Pest als Test-Runner.
- Konfigurationen: `composer.json` (Scripts), `phpunit.xml` (ENV), `testbench.yaml` (Workbench/DB/Migrationen), `tests/TestCase.php` (App/Panel/Middleware).

---

### Lokales Setup (gekapselt im Paket)

1) In das Paket wechseln

```bash
cd packages/item
```

2) Abhängigkeiten installieren und Workbench vorbereiten

```bash
composer install
composer prepare   # ruft testbench package:discover auf
composer build     # erstellt u.a. SQLite-DB, führt migrate:fresh aus (siehe testbench.yaml)
```

3) Tests ausführen

```bash
composer test      # ruft pest aus
# oder
vendor/bin/pest
```

4) Optional: Workbench lokal serven (für manuelle Browser-Checks)

```bash
composer serve
```

Hinweise:

- `tests/TestCase.php` setzt u.a. den App Key dynamisch und konfiguriert den Filament-Panel inkl. Middleware-Reihenfolge. Der Session-Driver wird für Tests auf `array` gesetzt, damit der `ViewErrorBag`/Livewire zuverlässig funktioniert.
- `testbench.yaml` enthält Build-Steps wie `create-sqlite-db`, `db-wipe` und `migrate-fresh`, sodass die Paket-DB isoliert bleibt.

### Testcase (Root-App verwenden)

Um die Testcases in den Packages zu überschreiben, kann der `Tests\TestCase` der Root‑App genutzt werden, wenn du die Tests aus der App ausführst (z. B. in `tests/Pest.php`):

```php
uses(Tests\TestCase::class)->in('../packages/*/tests/Feature', '../packages/*/tests/Unit');
```

Damit wird beim Ausführen der Tests aus der App der Root‑`TestCase` verwendet. Führst du die Tests hingegen innerhalb des Pakets aus, greift der paketinterne `Moox\Item\Tests\TestCase` (siehe `packages/item/tests/Pest.php`).

---

### Datenbank-Setups

Es gibt zwei gängige Modi: SQLite (schnell, Zero-Config) und MySQL (CI/realistischer).

#### A) SQLite (empfohlen für lokale, schnelle Tests)

- `testbench.yaml` enthält `create-sqlite-db` und Migration-Steps, die eine Test-SQLite-DB im Workbench-Kontext anlegen.
- Falls in `phpunit.xml` MySQL aktiv ist, kannst du temporär lokal via ENV auf SQLite wechseln:

```bash
DB_CONNECTION=sqlite \
DB_DATABASE=:memory: \
vendor/bin/pest
```

Oder du legst (konstant) in `phpunit.xml` einen SQLite-Block an, wenn du dauerhaft umstellen willst. Für schnelle Läufe genügt i.d.R. die ENV-Überschreibung.

#### B) MySQL (lokal und in CI)

Das mitgelieferte `phpunit.xml` ist bereits auf MySQL vorbereitet:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_HOST" value="127.0.0.1"/>
<env name="DB_PORT" value="3306"/>
<env name="DB_DATABASE" value="moox_item_test"/>
<env name="DB_USERNAME" value="root"/>
<env name="DB_PASSWORD" value=""/>
```

Lokale Einrichtung (Beispiel):

```bash
mysql -h 127.0.0.1 -u root -p -e "CREATE DATABASE IF NOT EXISTS moox_item_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# dann Tests mit MySQL fahren
DB_CONNECTION=mysql \
DB_HOST=127.0.0.1 \
DB_PORT=3306 \
DB_DATABASE=moox_item_test \
DB_USERNAME=root \
DB_PASSWORD=secret \
vendor/bin/pest
```

Wichtig: Obwohl `phpunit.xml` `SESSION_DRIVER=database` setzt, erzwingt `tests/TestCase.php` für den Testlauf `session.driver=array`, um Livewire-Fehlerbeutel stabil zu halten. Du musst die `sessions`-Tabelle daher nicht bereitstellen.

---

### Browser-Tests

- Das Paket nutzt `pestphp/pest-plugin-browser`. Stelle sicher, dass ein aktueller Chrome/Chromium verfügbar ist. Auf macOS reicht in der Regel das lokal installierte Chrome.
- Für Headless-Betrieb (lokal/CI) sind Panther-Flags sinnvoll:


Falls „visit()“ unbekannt ist oder der Browser nicht startet:

- Prüfe, ob das Browser-Plugin installiert und autoloaded ist.

---

### CI (GitHub Actions – Beispiel mit MySQL und Browser-Tests)

```yaml
name: item-tests

on: [push, pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: moox_item_test
        ports: ["3306:3306"]
        options: >-
          --health-cmd "mysqladmin ping -h 127.0.0.1 -uroot -proot"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 10

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, intl, pdo_mysql
          coverage: none

      - name: Install Composer deps
        working-directory: packages/item
        run: |
          composer install --no-interaction --no-progress --prefer-dist
          composer prepare
          composer build

      - name: Run tests (MySQL + headless browser)
        working-directory: packages/item
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: moox_item_test
          DB_USERNAME: root
          DB_PASSWORD: root
          PANTHER_NO_SANDBOX: 1
          PANTHER_CHROME_ARGUMENTS: "--headless --disable-gpu --no-sandbox --disable-dev-shm-usage"
        run: |
          vendor/bin/pest -vv
```

Optional: Wenn du statt MySQL SQLite in CI nutzen willst, kannst du den DB-Block weglassen und lediglich `composer build` + `vendor/bin/pest` ausführen.

---

### Häufige Fehler und Lösungen

- Fehlende App Key / „No application encryption key“:
  - Wird in `tests/TestCase.php` gesetzt. Wenn es dennoch auftritt, sicherstellen, dass deine Tests auch wirklich diese `TestCase` nutzen (`packages/item/tests/Pest.php`).

- „Session store not set“ / Fehler im Fehlerbeutel (Livewire):
  - Die Reihenfolge der Middleware ist in `tests/TestCase.php` korrekt (u.a. `StartSession` vor `ShareErrorsFromSession`). Außerdem wird `session.driver=array` erzwungen.

- „visit()“ ist nicht definiert:
  - Prüfe, ob `pestphp/pest-plugin-browser` installiert ist und Autoload (`composer dump-autoload`) korrekt läuft. Ggf. `composer require --dev symfony/panther` ergänzen.

- Datenbankverbindung fehlgeschlagen:
  - MySQL läuft nicht oder Credentials/DB fehlen. DB anlegen und ENV setzen (siehe MySQL-Abschnitt). Für schnelles Feedback auf SQLite wechseln.

- Migrationen/Tabellen fehlen (z.B. `users`):
  - `#[WithMigration('laravel', 'cache', 'queue', 'session')]` ist aktiv. Stelle sicher, dass `composer build` ausgeführt wurde. Ansonsten manuell `vendor/bin/testbench migrate` laufen lassen.

---

### Nützliche Befehle (Cheatsheet)

```bash
# Paket vorbereiten
composer install && composer prepare && composer build

# Alle Tests
composer test

# Nur Feature-Tests
vendor/bin/pest --testsuite=Feature

# Mit MySQL explizit
DB_CONNECTION=mysql DB_DATABASE=moox_item_test DB_USERNAME=root DB_PASSWORD=secret vendor/bin/pest

# Mit Browser-Headless-Flags
PANTHER_NO_SANDBOX=1 PANTHER_CHROME_ARGUMENTS="--headless --disable-gpu --no-sandbox --disable-dev-shm-usage" vendor/bin/pest
```

---


