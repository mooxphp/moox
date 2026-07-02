# Blocks Core (`core/blocks`)

Dieser Ordner enthaelt die State- und Mutationslogik fuer Block-Datenstrukturen.

## Zielbild

- `management.js` ist die zentrale Kompositions- und Export-Datei.
- Fachlogik liegt in kleinen Modulen pro Bereich.
- Die oeffentliche API bleibt stabil ueber die Legacy-Exports in `management.js`.

## Aktuelle Modulaufteilung

- `block-management.js`  
  Kernoperationen fuer Root-Bloecke: create/add/delete/change/move/style/media/column-structure.
- `child-management.js`  
  Child-Operationen fuer Container und Spalten.
- `table-management.js`  
  Tabellenstruktur, Zeilen/Spalten, Merge/Unmerge, Zellblock-Operationen.
- `tabs-management.js`  
  Tabs-Daten und Child-Operationen in Tabs.
- `accordion-management.js`  
  Accordion-Items, Verhalten und Child-Operationen.
- `checklist-management.js`  
  Checklist-Items (add/remove/toggle/move/text).
- `list-management.js`  
  List-Items (add/remove/move/text/style).
- `block-management-helpers.js`  
  Shared-Helper fuer Child-/Container-Basislogik.
- `editor-block-crud-methods.js`  
  Editor-Methoden fuer CRUD-nahe Block-Operationen auf Instanzebene.
- `editor-collection-methods.js`  
  Collection-Helfer fuer Block-Suchen, Traversal und Zugriffspfade.

## Wohin mit neuer Logik?

1. Immer zuerst vorhandenes Modul erweitern.
2. Nur bei klar neuer Domane ein neues `*-management.js` anlegen.
3. `management.js` nur fuer:
   - Imports
   - Factory-Wiring
   - stabile Exporte
4. Keine duplizierte Mutationslogik zwischen Modulen.

## Stabilitaetsregeln

- Keine Breaking Changes an den Exports aus `management.js`.
- Alle neuen Module auch in den Runtime-Assets spiegeln:
  - `packages/moox/block-editor/resources/editor/core/blocks/**`
  - `public/vendor/moox/block-editor/core/blocks/**`
- Nach Refactors immer:
  - Syntax-Check
  - kurze Smoke-Flows fuer betroffene Operationen
  - Source/Vendor-Sync pruefen
