## Moox Editor – Installationsanleitung

Ein wiederverwendbarer Block‑Editor für Laravel, Filament und Livewire.  
Dieses Paket stellt ein Filament‑Formularfeld bereit, das den JSON‑basierten Block‑Editor aus `resources/editor` im Backend einbindet.

---

## Voraussetzungen

- **PHP**: ^8.3  
- **Laravel**: ^12 || ^13  
- **Filament**: kompatibel mit aktueller Version (getestet mit `filament/filament`)  
- **Livewire**: ^4.0  
- **Tailwind CSS / Alpine.js**: werden im Editor‑Frontend über CDN geladen (siehe `resources/editor/README.md`).

---

## Dokumentationsindex

Nutze diese Uebersicht als Einstieg, je nachdem was du bearbeiten oder verstehen willst:

- `README.md`  
  Installation, Filament-Integration, Asset-Publishing, Grundkonfiguration.
- `API.md`  
  Template-API (Routes, Payloads, Responses, Auth/Authorization, cURL-Quickstart).
- `COMPONENT_DEVELOPMENT_GUIDELINE.md`  
  Reuse-first Regeln fuer neue/erweiterte Komponenten.
- `resources/editor/README.md`  
  Frontend-Editor Nutzung, Features, Konfigurationsflags (`data-*`), JSON-Workflow.
- `resources/editor/STRUCTURE.md`  
  Vereinfachte, aktuelle Ordner- und Modulstruktur des Editors.
- `resources/editor/components/blocks/README.md`  
  Block-Registry, Block-Contract, Lifecycle und Erweiterung neuer Blocktypen.
- `resources/editor/components/templates/README.md`  
  Template-Layer, erwartete Runtime-Bindings, Modals/Toolbars.
- `resources/editor/components/toolbar/README.md`  
  Historie + aktuelles Toolbar-Mapping auf `components/templates/toolbars` und `core/toolbar`.
- `resources/editor/core/blocks/README.md`  
  Core-Block-Management-Module und Stabilitaetsregeln.
- `resources/editor/core/shortcuts/README.md`  
  Shortcut-System und Erweiterung von Triggern.
- `tests/README.md`  
  Test-Scope (PHP + JS) und relevante Test-Kommandos.

---

## 1. Installation per Composer

### 1.1 Normale Installation (aus Packagist o.ä.)

Füge das Paket deinem Projekt hinzu:

```bash
composer require moox/block-editor
```

Laravel erkennt den Service Provider automatisch über `extra.laravel.providers` in der `composer.json` des Pakets:

```json
"extra": {
  "laravel": {
    "providers": [
      "Moox\\BlockEditor\\BlockEditorServiceProvider"
    ]
  }
}
```

### 1.2 Lokale Entwicklung (path‑Repository)

Wenn du das Paket lokal entwickelst, kannst du es wie im Beispielprojekt über ein `path`‑Repository einbinden:

```json
"repositories": [
  {
    "type": "path",
    "url": "packages/moox/block-editor",
    "options": {
      "symlink": true
    }
  }
],
"require": {
  "moox/block-editor": "dev-main"
}
```

Danach:

```bash
composer update moox/block-editor
```

---

## 2. Assets veröffentlichen

Der Editor bringt ein fertiges, statisches Frontend mit (JS/CSS), das ins `public`‑Verzeichnis kopiert werden muss.  
Verwende dazu den im Service Provider registrierten Publish‑Tag `moox-editor-assets`:

```bash
php artisan vendor:publish --tag=moox-editor-assets
```

Die Dateien werden anschließend unter folgendem Pfad verfügbar gemacht:

- `public/vendor/moox/block-editor/...`

Bei Updates des Pakets kannst du die Assets mit `--force` überschreiben:

```bash
php artisan vendor:publish --tag=moox-editor-assets --force
```

Stelle sicher, dass dein Webserver den `public`‑Ordner ausliefert.

---

## 3. Migrationen und Routen

Der `BlockEditorServiceProvider` lädt Migrationen und API‑Routen automatisch:

- Migrationen aus `database/migrations`
- Routen aus `routes/api.php`

Nach der Installation (oder nach Updates mit neuen Migrationen) solltest du einmalig:

```bash
php artisan migrate
```

ausführen.

---

## 4. Verwendung im Filament‑Formular

Das Paket stellt eine Filament‑Form Field‑Klasse `Moox\BlockEditor\Forms\Components\BlockEditor` bereit.  
Damit kannst du in deinen Formularen ein Block‑Editor‑Feld einbinden, das JSON im Model‑Attribut speichert.

### 4.1 Feld in einem Filament Resource Form

```php
use Filament\Forms\Form;
use Moox\BlockEditor\Forms\Components\BlockEditor;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            BlockEditor::make('content')
                ->label('Inhalt')
                ->required(),
        ]);
}
```

### 4.2 Verhalten des Feldes / Datenformat

Die `BlockEditor`‑Komponente kümmert sich darum, dass der Zustand immer als **JSON‑String** persistiert wird:

- Leerer Zustand → `'[]'`
- Bereits ein String → wird unverändert gespeichert
- Array/Collection → wird per `json_encode(..., JSON_UNESCAPED_UNICODE)` serialisiert

Im Model hast du also z.B. eine Spalte `content` (vom Typ `TEXT` oder `JSON`), in der der komplette Block‑Baum als JSON gespeichert ist.

---

## 5. Funktionsweise im Livewire‑/Filament‑Context

Intern wird ein Livewire‑Component‑View verwendet:

- View: `moox-editor::forms.components.block-editor`
- Livewire Component: `Moox\BlockEditor\Livewire\BlockEditorField`

Im Blade‑View des Feldes (`resources/views/livewire/block-editor-field.blade.php`) passiert u.a.:

- Initialer JSON‑State wird aus dem Feldzustand (`$state`) berechnet  
- Der statische Editor unter `public/vendor/moox/block-editor` wird eingebunden:
  - `styles/editor.css`
  - `block-editor-field.js`
  - `core/render/mount-editor.js`
  - `browser@4.js`
- Die Kommunikation zurück zu Livewire erfolgt über ein verstecktes Input‑Feld mit `wire:model.defer="state"`.

Du musst diesen Teil normalerweise nicht anpassen – wichtig ist nur, dass die veröffentlichten Assets erreichbar sind.

---

## 6. Frontend‑Editor im Detail

Der eigentliche Editor ist ein eigenständiges, modulares Frontend im Ordner:

- `resources/editor`

Dort findest du:

- `index.html`, `block-editor.js`, `styles/editor.css`
- `core/*` – State, Storage, Drag & Drop, Renderer, Shortcuts, Toolbar-Methoden, Utils  
- `components/*` – Block‑Implementierungen (Text/Media/Layout/Data), Templates, Block-Typen, Doku  
- `core/themes/*` – Theme-Methoden und Erweiterungen

Eine ausführliche Beschreibung des Frontends und des JSON‑Formats steht in:

- `resources/editor/README.md`
- `resources/editor/STRUCTURE.md`

---

## 7. Typische Installationsschritte (Übersicht)

1. **Paket installieren**
   - `composer require moox/block-editor`
2. **Assets veröffentlichen**
   - `php artisan vendor:publish --tag=moox-editor-assets`
3. **Migrationen ausführen**
   - `php artisan migrate`
4. **Filament‑Formularfeld verwenden**
   - `BlockEditor::make('content')` in deiner Resource/Form verwenden
5. **Speicherung prüfen**
   - Datenbankspalte (z.B. `content`) auf ausreichend Größe (TEXT/LONGTEXT/JSON) konfigurieren

Damit ist der Block‑Editor in deinem Filament‑/Laravel‑Projekt einsatzbereit.

