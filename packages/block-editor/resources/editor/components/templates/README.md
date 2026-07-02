# Templates (`components/templates`)

Diese README dokumentiert den kompletten Template-Layer des Editors in `packages/moox/block-editor/resources/editor/components/templates`.

Die Dateien in diesem Ordner liefern HTML als String (Template-Literale) und werden zentral im Editor zusammengesetzt. Die eigentliche Logik (State, Methoden, API-Calls, Events) liegt außerhalb der Templates, typischerweise in den Core- und Component-Modulen.

---

## 1) Ziel und Verantwortlichkeit

Der `templates`-Ordner ist für die **UI-Struktur** zuständig:

- Modal-Strukturen
- Sidebar-Layout
- Toolbars
- Notification-UI
- Developer-UI (optional)

Der Ordner ist **nicht** zuständig für:

- Business-Logik
- Datenvalidierung im Detail
- Persistenz/API
- Block-Manipulation selbst

Diese Trennung hilft, die Oberfläche austauschbar zu halten und die Methoden dahinter unabhängig weiterzuentwickeln.

---

## 2) Aufbau des Ordners

- `index.js`  
  Zentraler Aggregator. Baut ein Objekt aller Templates.
- `notification/notification.js`  
  Toast/Status-Benachrichtigungen.
- `sidebar/sidebar.js`  
  Linke Einstellungsleiste für den aktuell selektierten Block.
- `toolbars/block-toolbar.js`  
  Modal-Toolbar zum Hinzufügen von Blöcken und optionalen Theme-Vorlagen.
- `toolbars/floating-toolbar.js`  
  Kontext-Toolbar für Textformatierung bei Selektion.
- `developer/developer-tools.js`  
  Optionales Entwickler-UI (JSON ein-/ausblenden, Live-JSON-View).
- `modals/*.js`  
  Funktionsspezifische Modals:
  - `json-import-modal.js`
  - `theme-save-modal.js`
  - `theme-edit-modal.js`
  - `theme-import-modal.js`
  - `link-modal.js`
  - `confirm-modal.js`
  - `image-settings-modal.js`
  - `video-settings-modal.js`
  - `embed-settings-modal.js`

---

## 3) Zentrale Verkabelung (`index.js`)

### `getAllTemplates(options = {})`

Erzeugt ein strukturiertes Template-Objekt:

- `notification`
- `sidebar`
- `blockToolbar`
- `floatingToolbar`
- `developer.headerActions`
- `developer.jsonDisplay`
- `modals.*`

Option:

- `allowThemeTemplates?: boolean`
  - Default: `true`
  - Bei `false`: Toolbar ohne Theme-Tab (`Theme Vorlagen`)

### `getTemplate(name)`

String-basierter Zugriff über Dot-Notation, z. B.:

- `getTemplate('modals.link')`
- `getTemplate('developer.jsonDisplay')`

Wenn ein Pfadteil fehlt, wird sicher `''` zurückgegeben (kein Throw).

---

## 4) Template-Details pro Datei

## `notification/notification.js`

Zeigt rechts oben eine klickbare Notification mit Typ-abhängiger Farbe/Icon:

- `success`
- `error`
- `info`
- `warning`

Erwartete Bindings/Methoden:

- `notification.show`
- `notification.type`
- `notification.message`
- `hideNotification()`

Hinweis: Klick auf Container schließt die Notification, Close-Button stoppt Event-Bubbling explizit.

## `sidebar/sidebar.js`

Sidebar + Overlay für Block-Einstellungen:

- Öffnen/Schließen über `showSidebar`
- Overlay-Klick schließt Sidebar
- Content-Klicks stoppen Bubbling (`@click.stop`)

Wichtige UI-Abschnitte:

- Block-Typ umschalten (`changeBlockType`)
- Tailwind-Klassen bearbeiten (`block.classes`)
- HTML-ID (`block.htmlId`)
- Inline-CSS (`block.style`)
- Block-spezifisches Settings-HTML (`getBlockSettingsHTML(block)`)

Erwartete Bindings/Methoden:

- `showSidebar`, `closeSidebar()`
- `selectedBlockId`
- `getAllBlocks()`
- `childBlockTypes`
- `changeBlockType(id, type)`
- `clearBlockClasses(id)`
- `clearBlockHtmlId(id)`
- `clearBlockStyle(id)`
- `getBlockSettingsHTML(block)`

## `toolbars/block-toolbar.js`

Modal zum Hinzufügen neuer Blöcke.

Zwei Modi:

1. **Mit Theme-Tab** (`allowThemeTab = true`)
   - Tab `Block hinzufügen`
   - Tab `Theme Vorlagen`
2. **Ohne Theme-Tab** (`allowThemeTab = false`)
   - Nur Blockauswahl

Erwartete Bindings/Methoden:

- `showToolbar`, `closeBlockToolbar()`
- `showToolbarTab`
- `childBlockTypes`
- `addBlock(blockType)`
- `themes`
- `loadThemeFromToolbar(themeName)`

Besonderheit:

- Rendering der Blocktypen erfolgt dynamisch über `childBlockTypes` (kein hardcodierter Block-Katalog im Template).

## `toolbars/floating-toolbar.js`

Kontextabhängige Formatierungsleiste für selektierten Text.

Unterstützte Aktionen:

- Fett (`bold`)
- Kursiv (`italic`)
- Unterstrichen (`underline`)
- Durchgestrichen (`strikeThrough`)
- Ausrichtung (`left`, `center`, `right`)
- Text- und Hintergrundfarben
- Link setzen

Erwartete Bindings/Methoden:

- `showFloatingToolbar`
- `floatingToolbarPosition.top/left`
- `applyTextFormat(format)`
- `getFormatState(format)`
- `applyTextAlignmentToSelection(alignment)`
- `getTextAlignmentState(alignment)`
- `applyTextColor(color)`
- `applyBackgroundColor(color)`
- `openLinkInputForSelection()`

UI-Details:

- Nutzt `@mousedown.prevent`, um Selektion/Fokus beim Klicken zu erhalten.
- Enthält eigenes Dropdown-State-Snippet (`x-data="{ showColorPicker: false }"`).

## `developer/developer-tools.js`

Optionales Entwickler-UI:

- Header-Button: JSON anzeigen/ausblenden
- Readonly-Live-Textarea: zeigt aktuelle JSON-Struktur

Erwartete Bindings/Methoden:

- `developerJsonEnabled`
- `showJsonStructure`
- `getJSONDisplay()`

Einsatz:

- Debugging
- schnelle Sichtkontrolle der aktuellen Block-Struktur

## `modals/json-import-modal.js`

Importiert JSON-Struktur manuell per Textfeld.

Features:

- Live-Validierung
- Fehleranzeige
- Erfolgsanzeige
- Vorschau der Blockanzahl

Erwartete Bindings/Methoden:

- `showImportModal`, `closeImportModal()`
- `importJSONText`
- `validateImportJSON()`
- `importJSONError`, `importJSONValid`, `importJSONPreview`
- `confirmImportJSON()`

## `modals/theme-save-modal.js`

Speichert aktuelles Layout als Theme (Name erforderlich).

Erwartete Bindings/Methoden:

- `showSaveThemeModal`, `closeSaveThemeModal()`
- `newThemeName`
- `saveTheme()`
- `saveThemeError`
- `blocks.length` (Info-Anzeige)

## `modals/theme-edit-modal.js`

Bearbeitet den Namen eines vorhandenen Themes.

Erwartete Bindings/Methoden:

- `showEditThemeModal`, `closeEditThemeModal()`
- `editThemeName`
- `updateTheme()`
- `editThemeError`

## `modals/theme-import-modal.js`

Import eines Themes über JSON-Datei-Upload.

Erwartete Bindings/Methoden:

- `showImportThemeModal`, `closeImportThemeModal()`
- `handleThemeFileImport($event)`

## `modals/link-modal.js`

Universelles Link-Modal für mehrere Kontexte:

- `selection` (Text-Selektion)
- `edit` (bestehender Link)
- `block` (Link-Block/URL mit optionalem Linktext)

Features:

- URL-Eingabe
- optionaler Link-Text (nur `block`)
- Target-Auswahl (`_self`, `_blank`, `_parent`, `_top`)
- Preview (nur `block`)
- Entfernen-Button (nur `edit`)

Erwartete Bindings/Methoden:

- `showLinkModal`, `closeLinkModal()`
- `linkModal.type`, `linkModal.url`, `linkModal.linkText`, `linkModal.target`, `linkModal.text`
- `selectedText`
- `saveLink()`
- `removeLink()`

## `modals/confirm-modal.js`

Generisches Confirm-Modal mit 2 Betriebsarten:

1. **Link-Follow-Modus** (`confirmModal.showLinkFollow`)
   - Aktionen: Bearbeiten / Link folgen
2. **Standard-Modus**
   - Abbrechen
   - Optional `Erweitern`
   - Bestätigen

Erwartete Bindings/Methoden:

- `showConfirmModal`, `closeConfirmModal()`
- `confirmModal.title`, `confirmModal.message`
- `confirmModal.showLinkFollow`, `confirmModal.showExtend`
- `confirmModal.onCancel`, `confirmModal.onConfirm`, `confirmModal.onExtend`
- `confirmAction()`

## `modals/image-settings-modal.js`

Bild-Einstellungen mit 2 Tabs:

- Datei auswählen (Upload-Pfad)
- URL eingeben

Features:

- Live-Vorschau
- Alt-Text
- Titel/Tooltip

Erwartete Bindings/Methoden:

- `showImageSettingsModal`, `closeImageSettingsModal()`
- `imageSettingsActiveTab`
- `imageSettingsBlockId`
- `selectImageFile(blockId)`
- `imageSettingsUrl`, `imageSettingsAlt`, `imageSettingsTitle`
- `saveImageSettings()`

## `modals/video-settings-modal.js`

Video-Einstellungen analog zu Bild-Einstellungen:

- Datei auswählen
- URL eingeben
- Vorschau im `<video>`-Tag
- Poster-URL
- Titel/Tooltip

Erwartete Bindings/Methoden:

- `showVideoSettingsModal`, `closeVideoSettingsModal()`
- `videoSettingsActiveTab`
- `videoSettingsBlockId`
- `selectVideoFile(blockId)`
- `videoSettingsUrl`, `videoSettingsPoster`, `videoSettingsTitle`
- `saveVideoSettings()`

## `modals/embed-settings-modal.js`

Embed-Einstellungen fuer externe Inhalte (z. B. YouTube/Vimeo) mit URL-Eingabe und Vorschau.

Erwartete Bindings/Methoden:

- `showEmbedSettingsModal`, `closeEmbedSettingsModal()`
- `embedSettingsBlockId`
- `embedSettingsUrl`
- `saveEmbedSettings()`

---

## 5) Gemeinsame UI-Konventionen

Über alle Templates hinweg konsistent:

- Alpine Visibility: `x-show`
- Alpine Transitions: `x-transition:*`
- Overlay-Dismiss: `@click.self="close...()"`
- Inneres Modal stoppt Bubbling: `@click.stop`
- Tailwind Utility-First Styling
- Zugängliche Grundmuster (Labels, Placeholder, visuelle Zustände)

---

## 6) Integration und Lebenszyklus

Typischer Ablauf:

1. `getAllTemplates()` erzeugt alle HTML-Strings.
2. Editor rendert/integriert diese Strings in die Gesamthülle.
3. Alpine bindet die Expressions an den Runtime-State.
4. Nutzeraktionen rufen Methoden der Editor-Instanz auf.
5. State-Änderungen spiegeln sich sofort in den Templates.

Die Templates sind damit deklarative View-Bausteine, die auf Runtime-API und State angewiesen sind.

---

## 7) Erweiterungsleitfaden

Beim Hinzufügen neuer Templates:

1. Neue Datei unter passender Unterstruktur erstellen (`modals/`, `toolbars/`, ...).
2. In `index.js` importieren.
3. In `getAllTemplates()` einhängen.
4. Falls nötig über `getTemplate('...')` erreichbar machen.
5. Nur UI im Template halten, Logik in bestehende Core/Component-Methoden integrieren.

Empfehlungen:

- Keine stillen neuen State-Namen ohne Gegenstück in Runtime-Logik.
- Bei Modals immer einheitliches Overlay/Transition/Close-Muster nutzen.
- Bei Formular-Inputs klar beschriften und Placeholder konsistent halten.

---

## 8) Abhängigkeiten und Risiken

Die Templates setzen voraus, dass die konsumierende Instanz die genannten Methoden/States bereitstellt.

Wenn ein erwartetes Binding fehlt, sind typische Effekte:

- Button ohne Wirkung
- nicht schließbares Modal
- leere Vorschau
- JavaScript-Fehler zur Laufzeit

Daher sollten Template-Änderungen immer zusammen mit einer kurzen Smoke-Prüfung der betroffenen User-Flows erfolgen (öffnen, bearbeiten, speichern, schließen).

---

## 9) Kurzfazit

Der `templates`-Ordner bildet die vollständige visuelle Schicht des Editors: modular, klar segmentiert und über `index.js` zentral zusammengeführt.  
Die Qualität der Integration hängt direkt davon ab, dass State-Namen und Methoden-Signaturen zwischen Template und Runtime exakt synchron bleiben.

