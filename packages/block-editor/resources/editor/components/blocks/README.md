# Block-Komponenten (`components/blocks`)

Dieser Ordner enthält die Render- und Lebenszyklus-Logik für alle Editor-Blocktypen.
Jeder Blocktyp ist als eigenes Modul umgesetzt und wird zentral über `index.js` registriert.

## Ordneraufbau

```text
components/blocks/
├─ index.js                # Zentrale Registry + Fallback + Validierung
├─ text/
│  ├─ paragraph.js
│  ├─ heading.js
│  ├─ code.js
│  ├─ quote.js
│  ├─ callout.js
│  ├─ checklist.js
│  └─ list.js
├─ media/
│  ├─ image.js
│  ├─ video.js
│  └─ embed.js
├─ layout/
│  ├─ divider.js
│  ├─ two-column.js
│  ├─ three-column.js
│  ├─ group.js
│  ├─ link.js
│  └─ toggle-list.js
└─ data/
   ├─ table.js
   ├─ tabs.js
   └─ accordion.js
```

## Gemeinsamer Komponenten-Contract

Alle Blockmodule folgen demselben Grundmuster:

- `type`: Eindeutiger Typ-Key (muss zu `BLOCK_TYPES` passen).
- `options`: Referenz auf `BLOCK_TYPES.<type>` aus `components/block-types.js`.
- `structure`: Shape für neue Instanzen (z. B. `content`, `children`, `tableData`, Media-Felder).
- `renderHTML(block, context)`: Rendering auf Root-Ebene.
- `renderChildHTML(child, context)`: Rendering in verschachtelten Strukturen.
- `initialize(block, blockIdCounter)`: Initialisierung bei neu erstellten Blöcken.
- `ensureInitialized(block, blockIdCounter)`: Defensive Nachinitialisierung für geladene Daten.
- `cleanup(block)`: Bereinigung alter Felder beim Typwechsel.

Optionale Erweiterungen je nach Typ:

- `focusable` und `focus(element, block)` für Fokusverhalten.
- `getSettingsHTML(block, context)` für Sidebar-spezifische Einstellungen.

## Registry und Auflösung

`index.js` ist die zentrale Quelle für die Zuordnung `block.type -> Komponente`:

- `BlockComponents`: Mapping aller bekannten, renderbaren Typen.
- `validateBlockComponentRegistry()`: Prüft Abgleich mit `BLOCK_TYPES`.
- `getBlockComponent(type)`: Liefert Komponente oder Fallback `paragraph`.
- `initializeBlock(...)`, `ensureBlockInitialized(...)`, `cleanupBlock(...)`: Einheitlicher Zugriff auf Lifecycle-Funktionen.

Hinweis: Der interne Typ `column` ist strukturell und wird nicht als eigene Block-Komponente gerendert.

## Wichtige Verhaltensmuster in diesem Ordner

- **Textblöcke** (`paragraph`, `heading1..6`, `code`, `quote`, `callout`): `contenteditable`-basiertes Rendering mit Input/Blur-Commit oder containerbasiertem Child-Rendering.
- **Containerblöcke** (`twoColumn`, `threeColumn`, `group`, `toggleList`, `link`): Arbeiten mit `children` und Child-Rendering.
- **Datenblöcke** (`table`, `tabs`, `accordion`): Nutzen spezialisierte Datenstrukturen (`tableData`, `tabsData`, `accordionData`) mit eigenem Editor-Handling.
- **Media** (`image`, `video`, `embed`): Eigene Datenfelder, Platzhalter-Rendering und Settings-UI.

## Neuen Blocktyp integrieren

Damit ein neuer Typ vollständig integriert ist, müssen alle folgenden Stellen konsistent sein:

1. Typ in `components/block-types.js` ergänzen.
2. Komponente unter `components/blocks/**` erstellen.
3. In `components/blocks/index.js` importieren und in `BlockComponents` registrieren.
4. Initialisierung/Cleanup sauber für den Datenshape umsetzen.
5. Rendering für Root und Child (`renderHTML`/`renderChildHTML`) definieren.
6. Prüfen, ob der Typ Container-Verhalten (`children`, `columnCount`) benötigt.

## Runtime-Hinweis

Die Source-Dateien liegen unter:

- `packages/moox/block-editor/resources/editor/components/blocks/**`

Die ausgelieferten Runtime-Assets liegen parallel unter:

- `public/vendor/moox/block-editor/components/blocks/**`

Bei Codeänderungen an Block-Implementierungen sollten beide Pfade synchron gehalten werden, damit Editor und veröffentlichte Assets identisches Verhalten haben.

