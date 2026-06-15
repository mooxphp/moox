# Moox Builder

Runtime-Feldgruppen für Filament-Resources — ACF-ähnlich, aber rein Laravel/Filament.

Admins definieren Felder im Panel. Werte werden in typisierten `builder_field_values`-Zeilen gespeichert (kein JSON-Blob am Model, kein WordPress/postmeta).

---

## Inhaltsverzeichnis

1. [Architektur-Überblick](#architektur-überblick)
2. [Die zwei Schichten](#die-zwei-schichten)
3. [Datenbank](#datenbank)
4. [Runtime-Ablauf](#runtime-ablauf)
5. [Installation](#installation)
6. [Resource anbinden](#resource-anbinden)
7. [Feldgruppen im Admin](#feldgruppen-im-admin)
8. [Feldtypen & Capabilities](#feldtypen--capabilities)
9. [Konfiguration](#konfiguration)
10. [Erweiterung](#erweiterung)
11. [Paketstruktur](#paketstruktur)
12. [Testen](#testen)
13. [Grenzen & Roadmap](#grenzen--roadmap)

---

## Architektur-Überblick

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         ADMIN (Definition)                              │
│  Filament → Felder → Feldgruppen (FieldGroupResource)                   │
│       ↓                                                                 │
│  builder_field_groups / builder_fields / builder_field_options          │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                          DefinitionRegistry (Cache)
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      RUNTIME (Consumer-Resources)                       │
│  ItemResource + HasCustomFields                                         │
│       ↓                                                                 │
│  EntityRegistry → LocationMatcher → SchemaCompiler → Filament-Sections  │
│       ↓                                                                 │
│  PersistCustomFields (RecordSaved) → TypedValueDriver                   │
│       ↓                                                                 │
│  builder_field_values                                                   │
└─────────────────────────────────────────────────────────────────────────┘
```

**Kernprinzip:** Definition und Speicherung sind strikt getrennt.

| Schicht | Frage | Wo |
|---------|-------|-----|
| **Definition** | Welche Felder gibt es? | `builder_field_*` Tabellen + Admin-UI |
| **Speicher** | Wo liegen die Werte? | `builder_field_values` + `TypedValueDriver` |

---

## Die zwei Schichten

### 1. Definition Layer

Verantwortlich für **was** angezeigt wird und **wo** (Location).

| Komponente | Aufgabe |
|------------|---------|
| `FieldGroupResource` | Filament-CRUD für Feldgruppen |
| `FieldGroupPersistence` | Speichert Gruppen, Felder, Optionen, Location Rules |
| `DefinitionRegistry` | Lädt aktive Gruppen, cached als Arrays |
| `LocationMatcher` | Prüft `location_rules` gegen `LocationContext` |
| `EntityRegistry` | Mappt registrierte Filament-Resources → Entity-Keys |
| `SchemaCompiler` | Baut Filament-`Section`s aus Definitionen |

Definitionen werden als **DTOs** (`FieldGroupDefinition`, `FieldDefinition`) transportiert — nicht als lose Eloquent-Models im Runtime-Pfad.

### 2. Storage Layer

Verantwortlich für **Werte** pro Datensatz.

| Komponente | Aufgabe |
|------------|---------|
| `ValueStore` (Interface) | `load()` / `save()` Vertrag |
| `TypedValueDriver` | Einziger Treiber: typisierte Spalten |
| `ValueStoreResolver` | Löst Treiber aus `config/builder.php` auf |
| `TypedValueColumns` | Mapping Feldtyp → DB-Spalte |
| `CustomFieldsManager` | Orchestriert Laden/Speichern für Resources |
| `PersistCustomFields` | Listener auf Filament `RecordSaved` |

Werte hängen **nicht** am Eloquent-Model (keine `custom_fields`-JSON-Spalte nötig).

---

## Datenbank

### `builder_field_groups`

| Spalte | Bedeutung |
|--------|-----------|
| `name` | Anzeigename (= Section-Titel im Formular) |
| `slug` | Technischer Schlüssel der Gruppe |
| `location_rules` | JSON: wo die Gruppe erscheint (siehe unten) |
| `placement` | Reserviert (`default`) |
| `settings` | Reserviert für Gruppen-Einstellungen |
| `sort` | Reihenfolge mehrerer Gruppen |
| `active` | Nur aktive Gruppen werden gerendert |

### `builder_fields`

Felder einer Gruppe: `name` (Feldschlüssel), `label`, `type`, `config`, `validation`, `sort`.

`parent_field_id` ist in der Migration vorgesehen (Repeater/Flexible Content — noch nicht implementiert).

### `builder_field_options`

Optionen für `select`, `radio`, `multiselect`, `checkbox_list`.

### `builder_field_values`

Eine Zeile pro Wert:

| Spalte | Feldtypen |
|--------|-----------|
| `entity` | z. B. `item` |
| `record_id` | ID des Datensatzes |
| `field_name` | Feldschlüssel |
| `value_string` | text, email, url, select, … |
| `value_text` | textarea |
| `value_decimal` | number |
| `value_date` | date |
| `value_datetime` | datetime |
| `value_boolean` | toggle |
| `value_json` | multiselect, checkbox_list |

Unique: `(entity, record_id, field_name)`.

### Location Rules (intern)

Im Admin wählst du **„Anzeigen bei“** (Multi-Select). Intern wird das zu:

```json
[
  [{ "param": "entity", "operator": "==", "value": "item" }],
  [{ "param": "entity", "operator": "==", "value": "product" }]
]
```

Jede innere Liste = AND-Gruppe, mehrere Gruppen = OR. Aktuell unterstützt der Matcher nur `param: entity` mit `==` / `!=`.

---

## Runtime-Ablauf

### Formular öffnen (Create/Edit)

```
1. Resource::form() enthält ...static::customFieldComponents()

2. HasCustomFields prüft EntityRegistry
   → nicht registriert? → leeres Array (keine Sections)

3. DefinitionRegistry::fieldGroupsFor(LocationContext)
   → lädt gecachte Gruppen
   → LocationMatcher filtert nach entity

4. SchemaCompiler::compile()
   → pro Gruppe eine Filament-Section
   → pro Feld eine Form-Komponente (TextInput, Select, …)
   → afterStateHydrated lädt Werte aus TypedValueDriver (nur Edit)
```

### Speichern

```
1. Filament speichert das Model (title, description, …)

2. Event RecordSaved wird gefeuert

3. PersistCustomFields::handle($record, $data, $page)
   → prüft: Resource nutzt HasCustomFields + ist registriert?

4. CustomFieldsManager::saveFromFormData()
   → extrahiert nur bekannte Feld-Keys aus $data
   → TypedValueDriver::save() → builder_field_values
```

**Wichtig:** Keine Page-Hooks (`afterCreate`, `mutateFormDataBeforeSave`) nötig.

### Cache

`DefinitionRegistry` cached unter `builder.definitions` als **PHP-Arrays** (keine serialisierten Objekte).

Invalidierung automatisch via `InvalidateDefinitionCacheObserver` bei Änderungen an Gruppen/Feldern/Optionen.

Manuell: `php artisan cache:forget builder.definitions`

---

## Installation

### Via Moox Installer

```bash
composer require moox/builder
php artisan moox:install
```

Migrations, Config, Seeder und `BuilderPlugin` auswählen.

### Manuell

```bash
composer require moox/builder
php artisan vendor:publish --tag=builder-config
php artisan vendor:publish --tag=builder-migrations
php artisan migrate
php artisan db:seed --class="Moox\Builder\Database\Seeders\BuilderSeeder"
```

Plugin im Panel:

```php
use Moox\Builder\Plugins\BuilderPlugin;

$panel->plugins([
    BuilderPlugin::make(),
]);
```

---

## Resource anbinden

### Schritt 1: Entity registrieren

Der Array-Key in `config('builder.entities')` ist der Entity-Identifier für Location Rules und Storage.

**Option A — Config** (`config/builder.php`):

```php
'entities' => [
    'item' => [
        'resource' => \Moox\Item\Resources\ItemResource::class,
        'label' => 'Items',
    ],
],
```

**Option B — Service Provider** (empfohlen für Moox-Packages):

```php
// ItemServiceProvider::packageBooted()
config([
    'builder.entities.item' => [
        'resource' => ItemResource::class,
        'label' => 'Items',
    ],
]);
```

### Schritt 2: Trait in der Filament-Resource

```php
use Moox\Builder\Concerns\HasCustomFields;

class ItemResource extends Resource
{
    use HasCustomFields;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            // eigene Felder …
            ...static::customFieldComponents(),
        ]);
    }
}
```

**Nicht nötig:**

- Trait oder Spalte am Eloquent-Model
- `$customFieldsEntity` (entfernt — kommt aus Config)
- Eigene Listener oder Page-Hooks
- Migration für JSON am Model

### Schritt 3: Feldgruppe im Admin

**Felder → Feldgruppen → Erstellen**

- **Anzeigen bei:** `Items` (oder andere registrierte Entity)
- Felder definieren
- Aktiv lassen

---

## Feldgruppen im Admin

Navigation: **Felder → Feldgruppen**

| Bereich | Inhalt |
|---------|--------|
| **Allgemein** | Name, technischer Schlüssel, aktiv, Reihenfolge |
| **Zuordnung** | Multi-Select „Anzeigen bei“ (registrierte Resources) |
| **Felder** | Repeater: Bezeichnung, Typ, Feldschlüssel, Pflichtfeld, Einstellungen, Optionen |

Übersetzungen: `resources/lang/de/builder.php`, `en/builder.php`.

---

## Feldtypen & Capabilities

### Eingebaute Feldtypen (15)

| Key | Filament-Komponente |
|-----|---------------------|
| `text` | TextInput |
| `textarea` | Textarea |
| `number` | TextInput (numeric) |
| `email` | TextInput (email) |
| `url` | TextInput (url) |
| `password` | TextInput (password) |
| `select` | Select |
| `multiselect` | Select (multiple) |
| `checkbox_list` | CheckboxList |
| `radio` | Radio |
| `toggle` | Toggle |
| `date` | DatePicker |
| `datetime` | DateTimePicker |
| `time` | TimePicker |
| `color` | ColorPicker |

### Capabilities (pro Typ konfigurierbar)

| Capability | Wirkung |
|------------|---------|
| `MaxLength` | max. Zeichenlänge |
| `Placeholder` | Platzhaltertext |
| `PrefixSuffix` | Präfix/Suffix |
| `DefaultValue` | Standardwert |
| `HelperText` | Hilfetext unter dem Feld |
| `MinValue` / `MaxValue` / `Step` | Zahlenfelder |
| `Rows` | Textarea-Zeilen |
| `DisplayFormat` | Datumsformat |

Jeder Feldtyp implementiert `FieldType`: `key()`, `formComponent()`, `capabilities()`, optional `castValue()` und `hasOptions()`.

---

## Konfiguration

`config/builder.php`:

| Key | Default | Beschreibung |
|-----|---------|--------------|
| `default_driver` | `typed` | Aktiver Value-Store-Treiber |
| `drivers.typed` | `TypedValueDriver` | Klassenname des Treibers |
| `navigation_group` | `Felder` | Filament-Navigationsgruppe |
| `entities` | `[]` | Registrierte Resources (siehe oben) |

Env-Variablen: `BUILDER_DRIVER`, `BUILDER_NAVIGATION_GROUP`.

---

## Erweiterung

### Eigenen Feldtyp registrieren

```php
use Moox\Builder\FieldTypes\FieldType;
use Moox\Builder\Registry\FieldTypeRegistry;

$this->app->afterResolving(FieldTypeRegistry::class, function (FieldTypeRegistry $registry) {
    $registry->register(new MyCustomFieldType);
});
```

### Eigenen Storage-Treiber (optional)

Interface `ValueStore` implementieren, in `config/builder.php` unter `drivers` eintragen, `default_driver` setzen.

Aktuell ist nur `typed` produktiv im Einsatz.

### Validierungsregeln abfragen

```php
ItemResource::customFieldRules();
// ['feld-name' => ['required', 'max:255'], ...]
```

---

## Paketstruktur

```
packages/builder/
├── config/builder.php
├── database/
│   ├── migrations/          # 4 Tabellen
│   └── seeders/BuilderSeeder.php
├── resources/lang/{de,en}/builder.php
└── src/
    ├── BuilderServiceProvider.php
    ├── Concerns/HasCustomFields.php      # Consumer-Trait
    ├── Compiler/
    │   ├── LocationMatcher.php
    │   └── SchemaCompiler.php
    ├── Data/
    │   ├── FieldDefinition.php
    │   ├── FieldGroupDefinition.php
    │   └── LocationContext.php
    ├── FieldTypes/
    │   ├── FieldType.php
    │   ├── Capabilities/                   # Wiederverwendbare Feld-Einstellungen
    │   └── Types/                          # 15 Feldtypen
    ├── Listeners/PersistCustomFields.php
    ├── Models/                             # FieldGroup, Field, FieldOption, FieldValue
    ├── Observers/InvalidateDefinitionCacheObserver.php
    ├── Plugins/BuilderPlugin.php
    ├── Registry/
    │   ├── DefinitionRegistry.php
    │   ├── EntityRegistry.php
    │   └── FieldTypeRegistry.php
    ├── Resources/FieldGroupResource.php    # Admin-UI
    ├── Services/
    │   ├── CustomFieldsManager.php
    │   └── FieldGroupPersistence.php
    ├── Storage/
    │   ├── TypedValueDriver.php
    │   ├── ValueStore.php
    │   └── ValueStoreResolver.php
    └── Support/TypedValueColumns.php
```

---

## Testen

### Package-Tests

```bash
cd packages/builder && composer test
```

### Manuell im Panel

1. **Felder → Feldgruppen** — Demo-Gruppe „Fahrzeugdaten“ (nach Seeder)
2. **Items → Erstellen/Bearbeiten** — Section mit Custom Fields
3. Speichern, dann DB prüfen:

```sql
SELECT entity, record_id, field_name, value_string, value_decimal, value_date
FROM builder_field_values
WHERE entity = 'item';
```

4. Item erneut öffnen — Werte müssen geladen sein.

### Checkliste

| Check | Erwartung |
|-------|-----------|
| 4 Builder-Tabellen existieren | nach `migrate` |
| Entity in `builder.entities` registriert | z. B. via ItemServiceProvider |
| `BuilderPlugin` im Panel | Nav „Felder“ sichtbar |
| Feldgruppe mit „Anzeigen bei: Items“ | Section auf Item-Form |
| Werte in `builder_field_values` | typisierte Spalten befüllt |

---

## Grenzen & Roadmap

**Aktuell nicht implementiert:**

- Repeater / Flexible Content (`parent_field_id` reserviert)
- Query-Scopes auf Consumer-Models (`whereCustomField()` o. ä.)
- Tier-1 Auto-Discovery von Entities
- Location-Params über `entity` hinaus
- `placement`-Steuerung (Tab, Sidebar, …)

**Bewusst entfernt / nicht Ziel:**

- WordPress/postmeta-Treiber
- JSON-Spalte am Model
- Press/WP-Integration

---

## Lizenz

MIT
