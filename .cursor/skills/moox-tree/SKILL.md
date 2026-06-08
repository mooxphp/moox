---
name: moox-tree
description: >-
  Integrates moox/tree into Moox consumer packages (TreeIndexConfiguration,
  forwardFromResource, TreeIndexListRecords, RendersAsTreeIndexInspector,
  Filament plugin). Use only when the user explicitly invokes moox-tree skill
  or asks to integrate moox/tree into a resource.
disable-model-invocation: true
---

# moox/tree Integration Skill

Skill für die **Anbindung von `moox/tree` an Consumer-Packages** (z. B. `category`, `menu-builder`).

**Nicht im Scope:** Änderungen an `packages/tree/**` — dafür gilt `.cursor/rules/moox-tree-package.mdc`.

## Aufruf

Nur laden, wenn der User den Skill **explizit** anfordert (z. B. „nutze moox-tree skill“, `/moox-tree`).

## Pflicht-Workflow

1. **Scope prüfen** — Aufgabe betrifft `packages/tree/src/**`? → Stoppen, User auf Package-Regel verweisen.
2. **Kontext sammeln** — Consumer-Package, Model, bestehende Tabellen-Resource, Migration/Spalten.
3. **Entscheidungsbaum** — Für jede Gabelung in [decisions.md](decisions.md): wenn der Kontext die Wahl **nicht eindeutig** macht, **`AskQuestion` nutzen**. Nicht raten.
4. **Toolbar abfragen (Pflicht vor Implementierung)** — Suche, Filter und Sprach-Switcher in **einer** `AskQuestion` mit drei Fragen klären ([decisions.md §6](decisions.md#6-toolbar-suche-filter-sprach-switcher)), sofern der User es nicht schon gesagt hat. **Erst danach** Resource/Config erzeugen.
5. **Integration umsetzen** — Gewählten Pfad aus [integration.md](integration.md) anwenden.
6. **Verifizieren** — Checkliste in [integration.md](integration.md) abarbeiten.

## Entscheidungsgabelungen

| # | Thema | Wann fragen | Details |
|---|--------|-------------|---------|
| 1 | Baum-Modus | Model/Migration unklar | [decisions.md §1](decisions.md#1-baum-modus) |
| 2 | Resource-Muster | Grünfeld vs. bestehende Resource | [decisions.md §2](decisions.md#2-resource-muster) |
| 3 | Listen-Anbindung | Keine `table()` oder Sonderfall | [decisions.md §3](decisions.md#3-listen-anbindung) |
| 4 | Inspector | Keine Edit-Page / nur Label+Parent gewünscht | [decisions.md §4](decisions.md#4-inspector) |
| 5 | Fehlende Fähigkeit | Verhalten nicht per Config lösbar | [decisions.md §5](decisions.md#5-fehlende-fähigkeit) |
| 6 | Toolbar | **Immer vor `treeIndex()`** — Suche/Filter/Sprache nicht vom User vorgegeben | [decisions.md §6](decisions.md#6-toolbar-suche-filter-sprach-switcher) |

**AskQuestion-Regel:** Pro Gabelung eine Frage mit 2 Optionen (Ja/Nein oder 2–3 sinnvolle Varianten). Erst nach Antwort implementieren. Sind mehrere Gabelungen offen, **eine AskQuestion mit mehreren Questions** (max. 2–3 gleichzeitig) oder nacheinander.

**Toolbar-Block (Standard vor jeder neuen Tree-Resource):** Eine `AskQuestion` mit genau diesen drei Questions — nur überspringen, wenn der User es **explizit** gesagt hat (z. B. „ohne Sprach-Switcher“):

1. Suche in der Toolbar?
2. Filter/Tabs aus der Listen-Resource?
3. Sprach-Switcher?

## Feste Regeln (ohne Frage)

- **Keine Tree-Mechanik im Consumer** — kein eigenes Livewire für Baum-CRUD, keine Move/Reorder-Actions, keine kopierten Alpine-Stores.
- **Keine Model-Erweiterungen nur für Tree** — kein `getTreeLabel()`, kein `TreeNodeContract`; erlaubt: Spalten, Kalnoy `NodeTrait` bei Nested Set, domänenübliche Accessors → dann `labelColumnQueryable(false)`.
- **Inspector `$resource`** zeigt auf die **Basis-Resource** (Formular-Quelle), **nicht** auf `XxxTreeResource`.
- **List-Page** `extends TreeIndexListRecords` — Config wird in `mount()` registriert.
- **Panel:** Tree-Resource per Filament-Plugin registrieren.
- **Domänenfilter** nur via Config-Closures (`modifyQuery`, `applySearchUsing`, `applyLanguageUsing`) oder `forwardFromResource()` — keine Category-/Mandanten-Queries ins Package schieben.

## Referenzen im Repo

| Was | Pfad |
|-----|------|
| Goldstandard Moox | `packages/category/src/Resources/CategoryTreeResource.php` |
| List-Page + Tabs | `packages/category/src/Resources/CategoryResource/Pages/TreeListCategories.php` |
| Inspector | `packages/category/src/Resources/CategoryResource/Pages/TreeInspectorCategory.php` |
| Plugin | `packages/category/src/Plugins/CategoryTreePlugin.php` |
| Doku | `packages/tree/README.md` |
| Integrations-Regel | `.cursor/rules/moox-tree-integration.mdc` |

## Nach der Integration

Checkliste in [integration.md](integration.md#verifikations-checkliste) durchgehen. Tests nur anlegen, wenn der User es verlangt.
