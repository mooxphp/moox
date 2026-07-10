# Ordnerstruktur

Diese Struktur beschreibt den aktuellen Stand des Editors in vereinfachter Form.
Sie ist in Core‑Logik, UI‑Components und Datenbereiche gegliedert.

```
editor/
├── index.html                      # Einstiegspunkt der App (UI Shell)
├── block-editor.js                 # Alpine.js App-Logik (global)
├── styles/
│   └── editor.css                  # Editor-spezifische Styles
├── core/                           # Zentrale App-Logik (keine UI)
│   ├── state/
│   │   └── editor.js               # Entry für Editor-State
│   ├── io/
│   │   ├── storage.js              # Orchestrierung für Import/Export + Theme-Handling
│   │   ├── editor-json-methods.js  # JSON Import/Export fuer Editor-Inhalte
│   │   ├── editor-json-display-methods.js # Debounced Live-JSON-Ansicht
│   │   ├── import-validation.js    # Validierung fuer JSON-Import
│   │   ├── templates-api.js        # API-Client fuer Template-Endpunkte
│   │   └── theme-local-storage.js  # LocalStorage-Zugriffe fuer Themes
│   ├── drag/
│   │   └── drag-drop.js            # Drag & Drop Logik
│   ├── render/
│   │   ├── json-renderer.js        # JSON Debug/Preview
│   │   ├── editor-shell.js         # HTML-Grundgerüst für den Editor
│   │   └── mount-editor.js         # Mount-Logik für Alpine-Komponente
│   ├── blocks/
│   │   ├── management.js           # Komposition + stabile Legacy-Exports
│   │   ├── block-management.js     # Root-Block-Operationen
│   │   ├── child-management.js     # Child-/Spalten-Operationen
│   │   ├── table-management.js     # Tabellen-Operationen
│   │   ├── tabs-management.js      # Tabs-Operationen
│   │   ├── accordion-management.js # Accordion-Operationen
│   │   ├── checklist-management.js # Checklist-Operationen
│   │   ├── list-management.js      # Listen-Operationen
│   │   ├── block-management-helpers.js # Shared-Helper für Block-Management
│   │   └── README.md               # Modulgrenzen und Erweiterungsregeln
│   ├── shortcuts/
│   │   └── shortcuts.js            # Quick-Shortcuts (-, 1., …)
│   ├── toolbar/
│   │   ├── editor-ui-methods.js    # Block-Toolbar UI-Methoden
│   │   ├── editor-toolbar-methods.js # Floating-Toolbar-Methoden
│   │   └── *.js                    # Toolbar-Helper (Position, Format, Selection)
│   ├── themes/
│   │   └── editor-theme-methods.js  # Theme-Methoden (Speichern/Laden/Verwalten)
│   └── utils/
│       ├── dom.js                  # DOM-Utilities
│       ├── json.js                 # JSON-Utilities
│       ├── format.js               # Formatierungs-Utilities
│       └── index.js                # Utils-Export
├── components/                     # Modulare UI- und Block-Bausteine
│   ├── blocks/                     # Block-Definitionen (Render/Init/Struktur)
│   │   ├── index.js                # Export aller Block-Komponenten
│   │   ├── text/                   # Text-Blöcke
│   │   │   ├── paragraph.js
│   │   │   ├── heading.js
│   │   │   ├── list.js
│   │   │   ├── checklist.js
│   │   │   ├── quote.js
│   │   │   └── code.js
│   │   ├── media/                  # Medien-Blöcke
│   │   │   ├── image.js
│   │   │   ├── video.js
│   │   │   └── embed.js
│   │   ├── layout/                 # Layout-/Struktur-Blöcke
│   │   │   ├── divider.js
│   │   │   ├── group.js
│   │   │   ├── link.js
│   │   │   ├── two-column.js
│   │   │   ├── three-column.js
│   │   │   └── toggle-list.js
│   │   └── data/                   # Daten-Blöcke
│   │       ├── table.js
│   │       ├── tabs.js
│   │       └── accordion.js
│   ├── templates/                  # HTML-Templates (UI-Teile)
│   │   ├── index.js
│   │   ├── sidebar/
│   │   │   └── sidebar.js
│   │   ├── notification/
│   │   │   └── notification.js
│   │   ├── developer/
│   │   │   └── developer-tools.js  # Dev-/Debug-Tools im UI
│   │   ├── modals/                 # Dialoge (Import/Export/Settings/Themes)
│   │   │   ├── json-import-modal.js
│   │   │   ├── theme-import-modal.js
│   │   │   ├── theme-edit-modal.js
│   │   │   ├── theme-save-modal.js
│   │   │   ├── video-settings-modal.js
│   │   │   ├── image-settings-modal.js
│   │   │   ├── embed-settings-modal.js
│   │   │   ├── confirm-modal.js
│   │   │   └── link-modal.js
│   │   └── toolbars/               # Toolbar-HTML-Templates
│   │       ├── block-toolbar.js
│   │       └── floating-toolbar.js
│   ├── toolbar/                    # Doku-Ordner (historisch; keine aktive Toolbar-Logik)
│   │   └── README.md
│   └── block-types.js              # Zentrale Block-Typ-Konfiguration
└── README.md                       # Projektübersicht / Doku
```
