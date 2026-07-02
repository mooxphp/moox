# Toolbar-Doku (`components/toolbar`)

Der Ordner `components/toolbar` enthaelt aktuell **nur diese Dokumentation**.
Die aktive Toolbar-Implementierung liegt nicht mehr hier, sondern in:

- `components/templates/toolbars/*` (HTML-Templates)
- `core/toolbar/*` (Methoden/Verhalten)
- `core/render/editor-shell.js` (Einbindung in die Editor-Shell)

## Aktueller Architekturstand

Die Toolbar-Funktionalitaet ist heute in zwei Ebenen getrennt:

1. **Template-Ebene**  
   Liefert Markup als String-Templates:
   - `components/templates/toolbars/block-toolbar.js`
   - `components/templates/toolbars/floating-toolbar.js`

2. **Runtime-Ebene**  
   Liefert State und Methoden in der Alpine-Instanz:
   - `core/toolbar/editor-ui-methods.js`
   - `core/toolbar/editor-toolbar-methods.js`
   - weitere Helpers unter `core/toolbar/*`

Die Alpine-Root-Komponente wird in `block-editor.js` aufgebaut und mischt die Toolbar-Methoden ein.

## Relevante State-Properties

Wichtige Toolbar-bezogene States in `block-editor.js`:

- `showToolbar`
- `showToolbarTab`
- `toolbarSearchQuery`
- `showFloatingToolbar`
- `floatingToolbarPosition`
- `selectedText`
- `selectedRange`

## Relevante Runtime-Methoden

Aus `core/toolbar/editor-ui-methods.js` (Block-Toolbar):

- `openBlockToolbar()`
- `closeBlockToolbar()`
- `matchesToolbarSearch(blockType, config)`
- `loadThemeFromToolbar(themeName)`

Aus `core/toolbar/editor-toolbar-methods.js` (Floating-Toolbar):

- `handleTextSelection()`
- `setFloatingToolbarPosition(...)`
- `applyTextFormat(format)`
- `removeFormatting()`
- `applyTextAlignmentToSelection(alignment)`
- `applyTextColor(color)`
- `applyBackgroundColor(color)`
- `openLinkInputForSelection()`

## Rendering und Einbindung

Die Shell bindet Toolbars ueber das Template-Objekt ein:

- `templates.blockToolbar` wird im Editor-Container gerendert
- `templates.floatingToolbar` wird lazy per `x-if="showFloatingToolbar"` gerendert

Siehe:

- `core/render/editor-shell.js`
- `components/templates/index.js`

## Hinweise fuer Aenderungen

Wenn du Toolbar-Verhalten anpasst:

1. Markup in `components/templates/toolbars/*` anpassen.
2. Methoden/State in `core/toolbar/*` bzw. `block-editor.js` anpassen.
3. Sicherstellen, dass Signaturen zwischen Template-Bindings und Runtime exakt passen.
4. Kurz Smoke-checken: Block hinzufuegen, Suche in Block-Toolbar, Text selektieren, Formatierung anwenden.

## Historischer Hinweis

Aeltere Doku und fruehere Stands hatten eigene JS-Module unter `components/toolbar/*`.
Dieser Aufbau ist im aktuellen Source-Stand nicht mehr aktiv.
