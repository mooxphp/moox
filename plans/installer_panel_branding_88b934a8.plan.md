---
name: Installer Panel Branding
overview: Nach make:filament-panel patcht der Installer die erzeugte App-Panel-Provider-Datei fest mit primary Pink und brandLogo (fester public-Pfad). Keine Prompts, kein globales Core-Branding; später kann ein Theme die Werte übernehmen.
todos:
  - id: revert-global-branding
    content: "Falls vorhanden: globales FilamentColor / MooxPanelBranding / Paket-Panel-Farben zurücknehmen"
    status: completed
  - id: inspect-filament-stub
    content: Stub/Output von make:filament-panel (Filament 5 im Repo) ansehen, gezielte Insert/Replace-Regeln ableiten
    status: pending
  - id: patch-panel-file
    content: "PluginInstaller::createNewPanel nach Artisan Provider patchen: ->colors primary Color::Pink, ->brandLogo(asset(...)) mit festem relativen Pfad (z.B. images/logo.svg), use Color wenn nötig"
    status: pending
  - id: manual-check
    content: moox:install/Plugin-Flow mit neuem Panel durchspielen und generierte Datei prüfen
    status: pending
isProject: false
---

# Installer-lokales Filament-Branding (vereinfacht, ohne Auswahl)

## Später: Theme

Langfristig sollen Farbe und Logo aus einem **Theme** kommen. Dieser Plan ist bewusst **minimal**: feste Defaults im Installer-Patch, damit ihr jetzt weiterkommt, ohne globale Seiteneffekte oder Prompt-UX.

## Scope (aktuell)

- **Nur** die vom Installer erzeugte Datei unter [`PluginInstaller::createNewPanel()`](packages/core/src/Installer/Installers/PluginInstaller.php): nach erfolgreichem `make:filament-panel` auf `$expectedPath` patchen.
- **Primary:** fest `Color::Pink` (`Filament\Support\Colors\Color::Pink`) in `->colors([...])` — bestehende `->colors`-Zeile aus dem Stub **ersetzen**, nicht doppeln.
- **Logo:** fest ein **relativer Pfad unter `public/`** (im Plan z. B. `images/logo.svg`); in der Provider-Datei `->brandLogo(asset('images/logo.svg'))` setzen. Ob der Installer zusätzlich eine Platzhalter-Datei anlegt oder nur dokumentiert/hinweist wenn die Datei fehlt, bei Implementierung festlegen (minimal: nur patchen + kurzer `note`, dass `public/images/logo.svg` erwartet wird).
- **Keine Prompts**, keine Konfiguration im Installer für diese Version.
- **Kein** `FilamentColor::register` im Core — nur `->colors()` / `->brandLogo()` in genau dieser generierten Provider-Datei.

## Technik

- Patch-Logik analog zu [`registerPluginsInPanel()`](packages/core/src/Installer/Installers/PluginInstaller.php) (Datei lesen, Regex/Replace, zurückschreiben), oder kleine Hilfsklasse unter `packages/core/src/Installer/`.
- Einmal generiertes Beispiel gegen lokale Filament-Version prüfen, damit Insert/Replace robust ist.

```mermaid
flowchart LR
  createNewPanel[createNewPanel]
  artisan[make:filament-panel]
  patch[fester Patch Pink + brandLogo]
  return[return panelPath]
  createNewPanel --> artisan --> patch --> return
```

## Bereits erledigt

- Globales / Paket-weites Branding-Revert (vom Team erledigt).
