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
│  PersistCustomFields (RecordSaved) → CustomFieldsManager                │
│       ↓                                                                 │
│  builder_field_values (TypedValueColumns)                               │
└─────────────────────────────────────────────────────────────────────────┘
```

**Kernprinzip:** Definition und Speicherung sind strikt getrennt.

| Schicht | Frage | Wo |
|---------|-------|-----|
| **Definition** | Welche Felder gibt es? | `builder_field_*` Tabellen + Admin-UI |
| **Speicher** | Wo liegen die Werte? | `builder_field_values` + `TypedValueColumns` |

---

## Die zwei Schichten

### 1. Definition Layer

Verantwortlich für **was** angezeigt wird und **wo** (Location).

| Komponente | Aufgabe |
|------------|---------|
| `FieldGroupResource` | Filament-CRUD für Feldgruppen |
| `FieldGroupPersistence` | Speichert Gruppen, Felder, Optionen, Location Rules, verschachtelte Felder; migriert JSON-Werte bei Umbenennung/Löschen verschachtelter Subfelder |
| `FieldGroupValidator` | Prüft doppelte **speicherbare** Feldschlüssel (global pro Gruppe, inkl. Tabs) und Konflikte zwischen Gruppen |
| `DefinitionRegistry` | Lädt aktive Gruppen, cached als Arrays |
| `LocationMatcher` | Prüft `location_rules` gegen `LocationContext` |
| `EntityRegistry` | Findet Filament-Resources mit `HasCustomFields` → Entity-Keys |
| `SchemaCompiler` | Baut Filament-Sections, Tabs, Layout-Felder aus Definitionen |

Definitionen werden als **DTOs** (`FieldGroupDefinition`, `FieldDefinition`) transportiert — nicht als lose Eloquent-Models im Runtime-Pfad.

### 2. Storage Layer

Verantwortlich für **Werte** pro Datensatz.

| Komponente | Aufgabe |
|------------|---------|
| `TypedValueColumns` | Mapping Feldtyp → DB-Spalte (`value_string`, `value_json`, …) |
| `CustomFieldsManager` | Laden/Speichern für Resources, Option-Validierung, Hydration-Cache |
| `FieldValueValidator` | Validierung verschachtelter Werte (Repeater, Group, Flexible Content) |
| `FieldValuePurger` | Löscht Werte bei Feld-/Gruppenänderungen (Root-Felder) |
| `CompoundFieldValueMigrator` | Benennt/entfernt verschachtelte Schlüssel in `value_json` (Group, Repeater, Flexible Content) |
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

Felder einer Gruppe: `name`, `label`, `type`, `config`, `validation`, `sort`.

**`parent_field_id`** verknüpft Unterfelder mit Layout-Feldern (Group, Repeater, Flexible-Content-Layouts). Die Baumstruktur wird rekursiv in `FieldGroupPersistence` synchronisiert.

### `builder_field_options`

Optionen für `select`, `radio`, `multiselect`, `checkbox_list`, `button_group`.

### `builder_field_values`

Eine Zeile pro Wert:

| Spalte | Feldtypen |
|--------|-----------|
| `entity` | z. B. `item` |
| `record_id` | ID des Datensatzes |
| `field_name` | Feldschlüssel |
| `value_string` | text, email, url, select, password, oembed, … |
| `value_text` | textarea, rich_text |
| `value_decimal` | number, range |
| `value_date` | date |
| `value_datetime` | datetime |
| `value_boolean` | toggle |
| `value_json` | multiselect, checkbox_list, link, group, repeater, flexible_content |

Unique: `(entity, record_id, field_name)`.

### Location Rules (intern)

Im Admin wählst du **„Anzeigen bei“** (Multi-Select). Intern wird das zu:

```json
[
  [{ "param": "entity", "operator": "==", "value": "item" }],
  [{ "param": "entity", "operator": "==", "value": "record" }]
]
```

Jede innere Liste = AND-Gruppe, mehrere Gruppen = OR. Aktuell unterstützt der Matcher nur `param: entity` mit `==` / `!=`. Ohne Zuordnung (`Anzeigen bei` leer) erscheint die Gruppe in keinem Formular.

---

## Runtime-Ablauf

### Formular öffnen (Create/Edit)

```
1. Resource::form() enthält ...static::customFieldComponents()

2. HasCustomFields → DefinitionRegistry::fieldGroupsFor(LocationContext)
   → lädt gecachte Gruppen
   → LocationMatcher filtert nach entity

3. SchemaCompiler::compile()
   → pro Gruppe eine Filament-Section
   → Layout-Felder: Tabs, Group, Repeater, Flexible Content (Builder)
   → afterStateHydrated lädt Werte via CustomFieldsManager (ein Query pro Datensatz, gecacht)
   → Filament Builder: hydrateItems() für UUID-basierte Block-Keys
```

### Speichern

```
1. Filament speichert das Model (title, description, …)

2. Event RecordSaved wird gefeuert

3. PersistCustomFields::handle($record, $data, $page)
   → prüft: Resource nutzt HasCustomFields?

4. CustomFieldsManager::saveFromFormData()
   → extrahiert bekannte Feld-Keys aus $data
   → FieldValueValidator + OptionValueRules
   → updateOrCreate in builder_field_values
```

**Wichtig:** Keine Page-Hooks (`afterCreate`, `mutateFormDataBeforeSave`) nötig.

### Cache

`DefinitionRegistry` cached unter `builder.definitions` als **PHP-Arrays**.

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

### Schritt 1: Trait in der Filament-Resource

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

Der **Entity-Key** wird automatisch aus dem Model-Basename abgeleitet (`Item` → `item`). Abweichenden Key per `customFieldsEntity()` überschreiben:

```php
protected static function customFieldsEntity(): ?string
{
    return 'item';
}
```

### Schritt 2: Entity-Discovery

`EntityRegistry` findet Resources **automatisch** über alle registrierten Filament-Panels — jede Resource mit `HasCustomFields` erscheint im Multi-Select „Anzeigen bei“. Keine manuelle Config-Registrierung nötig.

### Schritt 3: Feldgruppe im Admin

**Felder → Feldgruppen → Erstellen**

- **Anzeigen bei:** z. B. `Items`, `Records`
- Felder definieren
- Aktiv lassen

**Nicht nötig:**

- Trait oder Spalte am Eloquent-Model
- Eigene Listener oder Page-Hooks
- Migration für JSON am Model
- Manuelle Entity-Config

---

## Feldgruppen im Admin

Navigation: **Felder → Feldgruppen**

| Bereich | Inhalt |
|---------|--------|
| **Allgemein** | Name, technischer Schlüssel, aktiv, Reihenfolge |
| **Zuordnung** | Multi-Select „Anzeigen bei“ (auto-discovered Resources) |
| **Felder** | Repeater: Bezeichnung, Typ, Feldschlüssel, Pflichtfeld |
| **Einstellungen** | Capability-Felder (nur wenn der Typ welche hat) |
| **Optionen** | Für Select/Radio/Multiselect/Checkbox-Liste |
| **Unterfelder** | Für Group und Repeater |
| **Layouts** | Für Flexible Content (Layout-Schlüssel + Unterfelder) |

Repeater-Zeilen sind standardmäßig eingeklappt und zeigen Typ, Schlüssel und Pflichtfeld im Label.

Übersetzungen: `resources/lang/de/builder.php`, `en/builder.php`.

---

## Feldtypen & Capabilities

### Eingebaute Feldtypen (25 wählbar)

| Kategorie | Keys |
|-----------|------|
| **Text** | `text`, `textarea`, `email`, `url`, `password`, `rich_text` |
| **Zahl** | `number`, `range` |
| **Auswahl** | `select`, `multiselect`, `checkbox_list`, `radio`, `button_group`, `toggle` |
| **Datum** | `date`, `datetime`, `time` |
| **Sonstiges** | `color`, `link`, `message`, `oembed` |
| **Layout** | `tab`, `group`, `repeater`, `flexible_content` |

Intern (nur in der DB, nicht wählbar): `flexible_layout` — definiert ein Layout innerhalb von Flexible Content.

### Layout-Felder

| Typ | Filament-Komponente | Speicher |
|-----|---------------------|----------|
| `tab` | `Tabs` / `Tab` (Marker, kein Wert) | — |
| `group` | `Repeater` (min/max 1) | `value_json` (Objekt) |
| `repeater` | `Repeater` | `value_json` (Array) |
| `flexible_content` | `Builder` mit Layout-Blöcken | `value_json` (Array mit `type` + `data`) |

Flexible Content entspricht ACF **Flexible Content**: pro Zeile ein wählbares Layout mit eigenen Unterfeldern.

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
| `MessageBody` | Hinweistext (message) |
| `RepeaterItems` | min/max Einträge (Repeater, Flexible Content) |

Jeder Feldtyp implementiert `FieldType`: `key()`, `formComponent()`, `capabilities()`, optional `castValue()`, `hasSubFields()`, `hasLayouts()`.

### Validierung

- **Pflichtfelder** und **Capabilities** werden als Filament-Regeln auf die Komponente angewendet.
- **Verschachtelte Werte** (Repeater, Group, Flexible Content) werden zusätzlich durch `FieldValueValidator` geprüft — u. a. leere Repeater-Zeilen und unbekannte Layouts.

---

## Konfiguration

`config/builder.php`:

| Key | Default | Beschreibung |
|-----|---------|--------------|
| `navigation_group` | `Felder` | Filament-Navigationsgruppe für Feldgruppen |

Env: `BUILDER_NAVIGATION_GROUP`.

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

Übersetzung unter `builder::builder.field_types.{key}` in `resources/lang`.

### Validierungsregeln abfragen

```php
ItemResource::customFieldRules();
// ['feld-name' => ['required', 'max:255'], 'repeater.*.unterfeld' => ['required'], ...]
```

### Werte programmatisch laden

```php
use Moox\Builder\Services\CustomFieldsManager;

$values = app(CustomFieldsManager::class)->loadFormData(
    ItemResource::class,
    $item,
);
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
    ├── Concerns/HasCustomFields.php       # Consumer-Trait
    ├── Compiler/
    │   ├── LocationMatcher.php
    │   └── SchemaCompiler.php
    ├── Data/
    │   ├── FieldDefinition.php
    │   ├── FieldGroupDefinition.php
    │   └── LocationContext.php
    ├── FieldTypes/
    │   ├── FieldType.php
    │   ├── Capabilities/
    │   └── Types/                         # 26 Feldtypen
    ├── Listeners/PersistCustomFields.php
    ├── Models/                            # FieldGroup, Field, FieldOption, FieldValue
    ├── Observers/
    │   ├── InvalidateDefinitionCacheObserver.php
    │   └── PurgeFieldValuesObserver.php
    ├── Plugins/BuilderPlugin.php
    ├── Registry/
    │   ├── DefinitionRegistry.php
    │   ├── EntityRegistry.php
    │   └── FieldTypeRegistry.php
    ├── Resources/FieldGroupResource.php   # Admin-UI
    ├── Services/
    │   ├── CustomFieldsManager.php
    │   ├── FieldGroupPersistence.php
    │   ├── FieldGroupValidator.php
    │   ├── FieldValuePurger.php
    │   └── FieldValueValidator.php
    └── Support/
        ├── EntityModelDeletionRegistrar.php
        ├── OptionValueRules.php
        └── TypedValueColumns.php
```

---

## Testen

### Package-Tests

```bash
cd packages/builder && composer test
```

75 Tests (Stand: Paket-intern).

### Manuell im Panel

1. **Felder → Feldgruppen** — Demo-Gruppe „Fahrzeugdaten“ (nach Seeder)
2. **Items → Bearbeiten** — Custom-Field-Sections inkl. Tabs, Group, Repeater, Flexible Content
3. Speichern, dann DB prüfen:

```sql
SELECT entity, record_id, field_name, value_string, value_decimal, value_json
FROM builder_field_values
WHERE entity = 'item';
```

4. Item erneut öffnen — Werte müssen geladen sein.

### Seeder

```bash
php artisan db:seed --class="Moox\Builder\Database\Seeders\BuilderSeeder" --force
```

### Checkliste

| Check | Erwartung |
|-------|-----------|
| 4 Builder-Tabellen existieren | nach `migrate` |
| Resource nutzt `HasCustomFields` | erscheint unter „Anzeigen bei“ |
| `BuilderPlugin` im Panel | Nav „Felder“ sichtbar |
| Feldgruppe mit „Anzeigen bei: Items“ | Section auf Item-Form |
| Werte in `builder_field_values` | typisierte Spalten befüllt |
| Flexible Content sortierbar | ohne Fehler nach Speichern/Laden |

---

## Grenzen & Roadmap

**Implementiert:**

- Nested Fields via `parent_field_id` (Group, Repeater, Flexible Content)
- Layout-Felder: Tab, Group, Repeater, Flexible Content
- Entity-Discovery über `HasCustomFields` in Filament-Panels
- Verschachtelte Validierung (`FieldValueValidator`)
- Repeater min/max (`RepeaterItems` Capability)

**Aktuell nicht implementiert:**

- Werte-API / `HasBuilderValues`-Trait fürs Frontend (nur `CustomFieldsManager::loadFormData()`)
- Query-Scopes auf Consumer-Models (`whereCustomField()`)
- Relational-Felder (Post Object, Relationship, User, Taxonomy)
- Media-Felder (Image, File, Gallery)
- Clone-Feldtyp (ACF)
- Location-Params über `entity` hinaus
- `placement`-Steuerung (Sidebar, …)
- Conditional Logic im Formular

**Bewusst nicht Ziel:**

- WordPress/postmeta-Treiber
- JSON-Spalte am Model
- Accordion als eigener Feldtyp (Tabs + Sections reichen)

---

## Lizenz

MIT
