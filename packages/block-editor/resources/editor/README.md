# Block Editor

Ein modularer Block-Editor im Stil von BlockNote. Läuft ohne Build-Schritt, basiert auf Alpine.js und Tailwind CSS (CDN) und speichert Inhalte als JSON.

## Schnellstart

```bash
# Lokalen Webserver starten (empfohlen)
php -S localhost:8000

# Dann im Browser öffnen
# http://localhost:8000
```

Alternativ kannst du `index.html` direkt öffnen (für schnelle Tests).

## Features im Überblick

- **Block-Typen:** Paragraph, H1-H6, Code, Quote, Callout, Divider, Bild, Video, Embed, Link, Liste, Checkliste, Tabs, Accordion, Tabelle, Gruppe, Toggle-Liste, 2-/3-Spalten
- **Block-Management:** hinzufügen, löschen, verschieben (Buttons + Drag & Drop), Block-Typ wechseln
- **Verschachtelung:** Children-Blöcke in Containern (Spalten, Tabellen, Link-Block)
- **Text-Formatierung:** Floating Toolbar mit Bold/Italic/Underline/Strike, Alignment, Text- & Hintergrundfarbe, Links
- **Styling pro Block:** Tailwind-Klassen, Inline-CSS und optionale HTML-ID
- **JSON-Workflow:** Live-JSON-Ansicht (optional), JSON-Import-Modal (optional), LocalStorage; Kopieren in die Zwischenablage ist per API-Methode möglich (`copyJSONToClipboard()`)
- **Themes:** Speichern/Laden, optionales Speichern in `themes/` via File System Access API / API; weitere Theme-Aktionen (Import-Datei, Umbenennen, Löschen) als Alpine-Methoden vorhanden

## Einstellungen und Konfiguration

### Konfiguration über `data-*` am Editor-Root

Die Werte werden in `core/config/editor-config.js` ausgelesen. Attribute können auf `#editor-root` oder dem direkten Parent liegen. Boolesche Werte gelten als aktiv bei allem außer `0`, `false`, `no`, `off` (leer = Default).

| Attribut | Bedeutung | Standard |
|----------|-----------|----------|
| `data-block-json` | Initiales Blöcke-Array als JSON-String | — |
| `data-positive-block` | JSON-Array erlaubter Block-Typ-Schlüssel (Whitelist) | alle Typen |
| `data-negative-block` | JSON-Array ausgeschlossener Block-Typ-Schlüssel (Blacklist) | — |
| `data-moox-theme-templates` | Tab „Theme Vorlagen“, Theme speichern, Theme-Storage | `true` |
| `data-developer-json` | Bereich „JSON anzeigen“ / Live-JSON-Textarea im Header | `false` |
| `data-json-import` | Button **JSON importieren** (nur sichtbar, wenn auch Developer-JSON aktiv ist) | `false` |
| `data-add-components` | Block-Toolbar (`+`, Modal „Block hinzufügen“) | `true` |
| `data-template-slug` | Beim Start ein Theme/Vorlage mit diesem Slug laden | — |

**Beispiel** (vgl. `index.html`): `data-developer-json="1"`, `data-json-import="1"`, `data-moox-theme-templates="1"`, `data-add-components="1"`.

### Laravel Filament: `Moox\BlockEditor\Forms\Components\BlockEditor`

Auf dem Formularfeld (siehe `resources/views/forms/components/block-editor.blade.php`) steuern:

| Methode | Wirkung |
|---------|---------|
| `positiveBlock([...])` | Nur diese Block-Typen anbieten |
| `negativeBlock([...])` | Diese Block-Typen ausblenden |
| `templates(bool)` | Entspricht `data-moox-theme-templates` |
| `templateSlug('…')` | Entspricht `data-template-slug` |
| `showJson(bool)` | Entspricht `data-developer-json` |
| `addComponents(bool)` | Entspricht `data-add-components` |
| `showJsonImport(bool)` | Entspricht `data-json-import` |

### Bedienung im UI (wenn nicht deaktiviert)

- **Theme speichern:** Button im Kopfbereich öffnet ein Modal; der aktuelle Block-Baum wird unter einem Namen in der Theme-Verwaltung (LocalStorage / Backend je nach `Storage`-Implementierung) abgelegt.
- **Theme laden / erweitern:** Über **Block hinzufügen** → Tab **Theme Vorlagen**; Klick auf ein Theme öffnet einen Bestätigungsdialog (**ersetzen**, **an aktuelle Auswahl anhängen** oder abbrechen).
- **JSON anzeigen / ausblenden:** Erscheint im Header, wenn Developer-JSON aktiv ist; schaltet die **Live JSON-Struktur** (readonly-Textarea, aktualisiert mit Debounce über `getJSONDisplay()`).
- **JSON importieren:** Öffnet ein Modal zum Einfügen von JSON; Eingabe wird validiert (Debounce), danach **Importieren** ersetzt die aktuellen Blöcke.
- **Block hinzufügen:** Floating `+` bzw. Modal mit Kategorien; bei deaktivierten Komponenten entfallen diese Einstiegspunkte.

### Programmierbare Hilfen (Alpine-Komponente)

Über `Alpine.$data(editorElement)` stehen u. a. zur Verfügung: `getJSONDisplay()`, `copyJSONToClipboard()`, `saveToJSON()` (persistiert laut `Storage`), sowie Theme-Hilfen wie `openImportThemeModal()`, `openEditThemeModal(name)`, `showDeleteThemeConfirm(name)` — letztere haben in der mitgelieferten Oberfläche keinen festen Button, können aber für eigene Toolbars genutzt werden.

## Nutzung

1. Öffne die App (`index.html` oder via lokalen Server).
2. Klicke auf **Block hinzufügen** oder nutze die `+`-Buttons am Block.
3. Bearbeite Inhalte direkt im Block (ContentEditable).
4. Öffne die Sidebar (⚙️), um Typ, Klassen, CSS oder Children zu bearbeiten.
5. Optional: **Theme speichern** im Header; mit aktiviertem Developer-JSON **JSON anzeigen** bzw. **JSON importieren**.

### Quick-Shortcuts

- `#` + Space → H1
- `##` + Space → H2
- `###` + Space → H3
- `-` in einem leeren Paragraph → Liste (unordered)
- `1.` in einem leeren Paragraph → Liste (ordered)
- `>` in einem leeren Paragraph → Quote
- `|` in einem leeren Paragraph → Tabelle

## Block-Typen

- **Text:** Paragraph, H1, H2, H3, Quote, Code
- **Medien:** Bild (Upload/URL, Alt/Title), Video (Upload/URL, Poster/Title), Embed (URL)
- **Struktur:** Divider, Link-Block, Gruppe
- **Listen:** Liste (nummeriert/unnummeriert), Checkliste
- **Layout:** Zwei Spalten, Drei Spalten, Toggle-Liste
- **Daten:** Tabelle (Zellen, Header/Footer, Mergen), Tabs, Accordion

## JSON-Format (Beispiel)

```json
[
  {
    "id": "1736505602000",
    "type": "heading1",
    "content": "Meine Überschrift",
    "style": "",
    "classes": "",
    "htmlId": "",
    "children": [],
    "createdAt": "2026-01-20T10:00:00.000Z",
    "updatedAt": "2026-01-20T10:05:00.000Z"
  }
]
```

## Result-JSON zum Speichern (PHP / JavaScript)

Das aktuelle Editor-Inhalt liegt im Alpine.js-State als Array `blocks`. So kommst du an das JSON für dein Backend (z.B. PHP) oder zum Speichern per JavaScript.

### JavaScript: Result-JSON auslesen

Der Editor wird in `#editor-root` gemountet; die Alpine-Komponente ist das erste Kind-Element. Über `Alpine.$data()` erhältst du die Komponente und damit die Blöcke:

```javascript
// Editor-Root (das Element mit x-data="blockEditor()")
const editorEl = document.querySelector('#editor-root')?.firstElementChild;
if (!editorEl || typeof Alpine === 'undefined') return;

const editor = Alpine.$data(editorEl);

// Option A: Rohdaten (Array) – z.B. für fetch/JSON
const blocks = editor.blocks;
const jsonString = JSON.stringify(blocks, null, 2);

// Option B: Formatierter String wie in der Debug-Ansicht (mit Debounce)
const jsonDisplay = editor.getJSONDisplay();
```

Für saubere Spalten-Struktur vor dem Export (wie beim internen Speichern) kannst du vor dem Stringify die gleiche Logik nutzen wie der Editor – die Blöcke sind aber auch ohne diesen Schritt gültig.

### Formular-Submit an PHP

Wenn du das JSON per klassischem Formular an PHP schicken willst, z.B. ein verstecktes Feld füllen und mit absenden:

```html
<form method="post" action="/speichern.php" id="editor-form">
  <input type="hidden" name="block_json" id="block-json-input" value="">
  <button type="submit">Speichern</button>
</form>

<script>
  document.getElementById('editor-form').addEventListener('submit', function (e) {
    const editorEl = document.querySelector('#editor-root')?.firstElementChild;
    if (editorEl && typeof Alpine !== 'undefined') {
      const editor = Alpine.$data(editorEl);
      document.getElementById('block-json-input').value = JSON.stringify(editor.blocks);
    }
  });
</script>
```

In PHP liest du den Inhalt dann z.B. so:

```php
// speichern.php
$json = $_POST['block_json'] ?? '';
if ($json !== '') {
    $blocks = json_decode($json, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        // $blocks speichern (DB, Datei, etc.)
        file_put_contents('content.json', $json);
        // oder in Datenbank schreiben
    }
}
```

### Speichern per JavaScript (fetch / XHR)

Für ein reines JavaScript-Frontend (z.B. SPA oder AJAX-Submit):

```javascript
function saveEditorContent() {
  const editorEl = document.querySelector('#editor-root')?.firstElementChild;
  if (!editorEl || typeof Alpine === 'undefined') return;

  const editor = Alpine.$data(editorEl);
  const jsonString = JSON.stringify(editor.blocks);

  fetch('/api/content/save', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: jsonString
  }).then(res => res.json()).then(console.log).catch(console.error);
}
```

Der Endpoint (z.B. PHP) liest den Request-Body als JSON:

```php
// PHP (z.B. Laravel, oder plain PHP)
$json = file_get_contents('php://input');
$blocks = json_decode($json, true);
// $blocks persistieren
```

Kurz: **Result-JSON** = `Alpine.$data(editorRootElement).blocks` (als Array) bzw. `JSON.stringify(Alpine.$data(editorRootElement).blocks)` (als String für Submit/fetch).

## Projektstruktur (kurz)

```
editor/
├── index.html
├── block-editor.js
├── core/
│   ├── state/              # App-State Entry
│   ├── blocks/             # Block-Management
│   ├── io/                 # Storage + Import/Export
│   ├── drag/               # Drag & Drop
│   ├── render/             # JSON Rendering
│   ├── shortcuts/          # Shortcuts
│   ├── toolbar/            # Toolbar-Methoden (UI + Floating-Formatierung)
│   ├── themes/             # Theme-Methoden und Erweiterungen
│   └── utils/              # Utils
├── components/
│   ├── blocks/             # Block-Komponenten (text/media/layout/data)
│   ├── templates/          # HTML-Templates (Toolbar/Sidebar/Modals)
│   ├── toolbar/            # Doku-Ordner (historisch; keine aktive Logik)
│   └── block-types.js
└── styles/
    └── editor.css
```

## Abhängigkeiten

- **Alpine.js 3.15.2** (CDN)
- **Tailwind CSS (CDN)** – für Production empfiehlt sich ein eigener Build

## Hinweise zu Themes

- Themes werden primär über die Templates-API geladen/gespeichert.
- Falls die API nicht verfügbar ist, nutzt der Editor LocalStorage als Fallback.
- Import erfolgt über JSON-Dateien im Theme-Import-Modal.

## Browser-Support

Moderne Browser mit Support für ES Modules, LocalStorage, Drag & Drop und ContentEditable.

## Weitere Doku

- `STRUCTURE.md` – Projektstruktur
- `components/blocks/README.md` – Block-Komponenten Überblick
- `components/toolbar/README.md` – Toolbar-Details
- `core/shortcuts/README.md` – Shortcut-System

## Lizenz

Freie Verwendung für eigene Projekte.

