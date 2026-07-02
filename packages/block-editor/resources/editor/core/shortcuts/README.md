# Shortcuts

Diese Datei beschreibt die Block-Shortcuts in `shortcuts.js` und wie du neue Shortcuts hinzufuegst.

## Neuen Shortcut erstellen

1) Fuege einen neuen Eintrag in `BLOCK_SHORTCUTS` hinzu.
2) Gib dem Shortcut eine eindeutige `id` und ein `label`.
3) Definiere die `action` (was erstellt wird) und den `trigger` (welche Eingabe ausloest).
4) Optional: implementiere `match(...)`, wenn du komplexe Bedingungen brauchst.

Beispiel:

```
{
    id: 'quote',
    label: 'Zitat',
    action: { type: 'quote' },
    trigger: {
        keys: ['>'],
        codes: ['Period', 'NumpadDecimal'],
        text: ''
    }
}
```

## Bedeutung der Variablen

### BLOCK_SHORTCUTS
Array mit allen Shortcut-Definitionen. Die Reihenfolge ist wichtig, der erste Treffer gewinnt.

Jeder Shortcut kann folgende Felder enthalten:
- `id`: Eindeutiger Bezeichner des Shortcuts.
- `label`: Anzeigename fuer UI/Debugging.
- `action`: Objekt mit den Daten zur Block-Erstellung.
  - `type`: Block-Typ (z.B. `list`, `table`).
  - `listStyle`: Optional, Stil fuer Listen (`unordered` oder `ordered`).
- `trigger`: Einfache Ausloeser-Konfiguration.
  - `keys`: Array der `KeyboardEvent.key` Werte, die den Shortcut ausloesen.
  - `codes`: Array der `KeyboardEvent.code` Werte, die den Shortcut ausloesen.
  - `text`: Exakter Text, der im Block stehen muss (leer string = leerer Block).
- `match`: Optionaler Callback fuer eigene Logik.

### Welche Block-Komponenten reagieren?
Aktuell reagieren nur Paragraph-Bloecke auf Shortcuts, weil nur dort der
`handleQuickListShortcut(...)`-Handler auf `@keydown` gebunden ist:

- `components/blocks/text/paragraph.js`

Wenn ein anderer Block (z.B. `heading`, `quote`, `list`) Shortcuts ausloesen soll,
muss dessen Template ebenfalls `@keydown="handleQuickListShortcut(...)"` enthalten.
Damit bleibt das System flexibel und du kannst je Block entscheiden, ob
Shortcuts aktiv sein sollen.

### Uebersicht der Block-Typen
Es gibt eine programmatische Uebersicht ueber alle Shortcut-Ziele:

- `getBlockShortcutOverview()` liefert ein Array mit `id`, `label`, `type`, `listStyle`, `trigger`, `hasMatch`.
- `getBlockShortcuts()` liefert eine Kopie der kompletten Shortcut-Liste.

So kannst du z.B. eine Debug-Ansicht oder Doku automatisch erzeugen.

### trigger (ausfuehrlich)
Ein `trigger` wird mit dem aktuellen Tastendruck und dem aktuellen Block-Text verglichen.
Die Pruefung laeuft so:

1) `text`-Check:
   - Der aktuelle Text wird getrimmt (Whitespace am Anfang/Ende wird entfernt).
   - Wenn `trigger.text` gesetzt ist, muss der getrimmte Text exakt gleich sein.
   - Beispiel: `text: '#'` passt nur, wenn der Block genau `#` (ohne weitere Zeichen) enthaelt.
   - Beispiel: `text: ''` passt nur, wenn der Block leer ist.

2) `keys`-Check:
   - Wenn `trigger.keys` gesetzt ist, prueft der Shortcut `KeyboardEvent.key`.
   - Ein Treffer reicht aus, um den Shortcut auszuwaehlen.

3) `codes`-Check:
   - Wenn `trigger.codes` gesetzt ist, prueft der Shortcut `KeyboardEvent.code`.
   - Auch hier reicht ein Treffer aus.

Wichtig:
- `keys` und `codes` sind optional. Wenn eines davon passt, ist der Trigger erfolgreich.
- Die Reihenfolge in `BLOCK_SHORTCUTS` ist entscheidend: der erste passende Shortcut gewinnt.

Beispiele:
- Heading-Shortcuts: `text: '#'` plus `keys: [' ']` bedeutet: erst `#` tippen, dann Space.
- Listen-Shortcuts: `text: ''` plus `keys: ['-']` bedeutet: `-` in einem leeren Block.

### matchBlockShortcut(...)
Prueft alle `BLOCK_SHORTCUTS` und gibt den ersten passenden Shortcut zurueck.
Parameter:
- `key`: Tastendruck (aus `KeyboardEvent.key`).
- `code`: Tasten-Code (aus `KeyboardEvent.code`).
- `text`: Aktueller Text im Block.
- `event`: Das originale Keyboard-Event (nur fuer `match` relevant).

### matchesTrigger(...)
Interne Hilfsfunktion. Vergleicht `trigger` mit `key`, `code` und `text`.

### Dynamisch erweitern
Neue Shortcuts koennen zur Laufzeit hinzugefuegt werden:

- `registerBlockShortcut(shortcut, { prepend })` fuegt einen Shortcut hinzu.
  - `prepend: true` fuegt den Shortcut am Anfang ein (hoehere Prioritaet).
- `registerBlockShortcuts(shortcuts, { prepend })` fuegt mehrere Shortcuts hinzu.
