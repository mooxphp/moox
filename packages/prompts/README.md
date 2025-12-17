# Moox Prompts

CLI- und Web-kompatible Prompts für Laravel Artisan Commands – mit einem Flow, der im Browser Schritt für Schritt weiterläuft.

## Wie muss ein Flow-Command aussehen?

Damit ein Command sowohl in der CLI als auch im Web korrekt als Flow funktioniert, müssen nur diese Regeln erfüllt sein:

- **Von `FlowCommand` erben**  
  ```php
  use Moox\Prompts\Support\FlowCommand;
  use function Moox\Prompts\text;
  use function Moox\Prompts\select;

  class ProjectSetupCommand extends FlowCommand
  {
      protected $signature = 'prompts:project-setup';
      protected $description = 'Projekt Setup Wizard (CLI & Web)';
  ```

- **State als Properties ablegen** (werden im Web automatisch zwischen Steps gespeichert)  
  ```php
      public ?string $environment = null;
      public ?string $projectName = null;
  ```

- **Steps über `promptFlowSteps()` definieren** – Reihenfolge = Flow-Reihenfolge  
  ```php
      public function promptFlowSteps(): array
      {
          return [
              'stepIntro',
              'stepEnvironment',
              'stepProjectName',
              'stepSummary',
          ];
      }
  ```

- **Jeder Step ist eine `public function stepXyz(): void`** – idealerweise **ein Prompt pro Step**  
  ```php
      public function stepIntro(): void
      {
          $this->info('=== Projekt Setup ===');
      }

      public function stepEnvironment(): void
      {
          $this->environment = select(
              label: 'Welche Umgebung konfigurierst du?',
              options: [
                  'local' => 'Local',
                  'staging' => 'Staging',
                  'production' => 'Production',
              ],
              default: 'local',
          );
      }

      public function stepProjectName(): void
      {
          $this->projectName = text(
              label: 'Wie heißt dein Projekt?',
              placeholder: 'z.B. MyCoolApp',
              validate: 'required|min:3',
              required: true,
          );
      }

      public function stepSummary(): void
      {
          $this->info('--- Zusammenfassung ---');
          $this->line('Projekt: '.$this->projectName);
          $this->line('Environment: '.$this->environment);
      }
  }
  ```

- **Optionale Steps** kannst du einfach mit einem Guard am Anfang überspringen:
  ```php
  public array $features = [];

  public function stepLoggingLevel(): void
  {
      if (! in_array('logging', $this->features, true)) {
          return; // Step wird übersprungen
      }

      // Prompt …
  }
  ```

- **Andere Artisan-Commands aufrufen** – verwende im Flow immer `$this->call()` statt `Artisan::call()`, damit der Output auch im Web angezeigt wird:
  ```php
  public function stepPublishConfig(): void
  {
      $shouldPublish = confirm(
          label: 'Möchtest du die Config jetzt veröffentlichen?',
          default: true,
      );

      if (! $shouldPublish) {
          return;
      }

      $this->call('vendor:publish', [
          '--tag' => 'moox-prompts-config',
      ]);
  }
  ```

Mehr ist im Command nicht nötig – keine speziellen Flow-Methoden, keine eigene Persistenz.  
Der Rest (CLI/Web-Unterschied, State, Web-Oberfläche) wird komplett vom Package übernommen.

## Ausführung im Browser (Filament)

Nachdem du einen Flow-Command erstellt hast, kannst du ihn sowohl in der CLI als auch im Browser ausführen:

### CLI-Ausführung

```bash
php artisan prompts:project-setup
```

Der Command läuft wie ein normaler Laravel Artisan Command – alle Prompts werden direkt im Terminal angezeigt.

### Web-Ausführung

1. Öffne die Filament-Seite "Run Command" (wird automatisch im Navigation-Menü angezeigt)
2. Wähle einen Flow-Command aus der Liste
3. Klicke auf "Command starten"
4. Der Flow läuft Schritt für Schritt im Browser ab:
   - Jeder Step zeigt einen Prompt (Text-Input, Select, Multiselect, Confirm, etc.)
   - Nach jedem Step siehst du den Output des Steps
   - Du kannst jederzeit mit "Zurück zur Command-Auswahl" abbrechen
   - Nach erfolgreichem Abschluss wird der Button zu "Start new command" geändert

**Wichtig:** Alle Commands, die im Web ausgeführt werden, werden automatisch in der Datenbank geloggt (siehe [Command Execution Logging](#command-execution-logging)).

## Wie und warum wird Reflection verwendet?

Wenn du nur Commands schreibst, musst du dich nicht um Reflection kümmern.  
Damit du aber verstehst, was im Hintergrund passiert, hier eine kurze Erklärung.

- **Problem 1: Argumente & Optionen im Web setzen**  
  Laravel speichert Argumente/Optionen intern in einem geschützten Property `$input` deines Commands.  
  In der CLI kümmert sich der Artisan-Kernel darum, dieses Property zu setzen.  
  Im Web-Flow erzeugen wir aber selbst neue Command-Instanzen – und müssen `$input` daher manuell setzen.  
  Genau das macht `PromptFlowRunner::setCommandInput()` mit Reflection:
  - es findet das `input`-Property auf deinem Command-Objekt,
  - macht es kurz zugänglich,
  - und schreibt das aktuelle Input-Objekt hinein.  
  **Ergebnis:** In Flow-Commands kannst du überall ganz normal `argument()` und `option()` verwenden – egal ob der Command per CLI oder im Browser läuft.

- **Problem 2: Command-State zwischen Web-Requests merken**  
  Im Web besteht dein Flow aus mehreren HTTP-Requests. Ohne zusätzliche Logik wären Properties wie `$environment`, `$features`, `$projectName` im nächsten Step einfach weg.  
  `PromptFlowRunner` löst das mit zwei internen Methoden:
  - `captureCommandContext($command, $state)`  
    - liest per Reflection alle nicht-statischen Properties deiner konkreten Command-Klasse aus  
    - speichert einfache Werte (Scalars, Arrays, `null`) im `PromptFlowState::$context`
  - `restoreCommandContext($command, $state)`  
    - setzt beim nächsten Request alle gespeicherten Werte wieder zurück auf das neue Command-Objekt  
  **Ergebnis:** Für deinen Code fühlt es sich so an, als würde derselbe Command einfach weiterlaufen – du musst keine eigene Persistenz (Cache, Datenbank, Session, …) schreiben.

- **Problem 3: Package-Tools im Web initialisieren**  
  Viele Packages, die `Spatie\LaravelPackageTools` verwenden, registrieren ihre publishable Ressourcen (Config, Views, Migrations, Assets, …) nur im CLI-Kontext.  
  `WebCommandRunner` verwendet Reflection, um intern an das `package`-Objekt eines solchen Service Providers zu kommen und die `publishes(...)`-Registrierung auch im Web nachzuholen.  
  **Ergebnis:** Befehle wie `vendor:publish` funktionieren im Browser genauso zuverlässig wie in der CLI, obwohl Laravel dort eigentlich nicht im Console-Modus läuft.

**Wichtig:**  
Reflection wird nur in diesen internen Klassen des Packages verwendet, nicht in deinen Commands.  
Deine Commands bleiben normale Laravel-Commands – du musst nur:

- von `FlowCommand` erben,
- Properties für den State definieren,
- Steps in `promptFlowSteps()` auflisten,
- `step*`-Methoden schreiben (am besten ein Prompt pro Step).

Den Rest (Reflection, State, Web-Flow) übernimmt das Package für dich.

### Gibt es Alternativen ohne Reflection?

Ja – theoretisch könnten wir auf Reflection verzichten, aber das hätte Nachteile für dich als Nutzer:

- Für **Argumente & Optionen** könnten wir eine eigene API einführen (statt `argument()/option()`), oder erzwingen, dass du alles manuell über Properties/Arrays verwaltest. Das wäre weniger “Laravel-typisch” und schwerer zu verstehen.
- Für den **Command-State zwischen Steps** könnten wir dich z.B. eine Methode wie `flowContextKeys()` implementieren lassen, in der du alle zu speichernden Properties auflistest, oder dich zwingen, selbst Cache/DB/Session zu benutzen. Das wäre mehr Boilerplate und eine zusätzliche Fehlerquelle.
- Für **Spatie Package Tools im Web** bräuchten wir entweder Änderungen im Spatie-Package selbst oder eine eigene, manuelle Konfiguration aller publishbaren Pfade – beides würde die Einrichtung deutlich komplizierter machen.

Aus diesen Gründen kapseln wir die Reflection-Nutzung bewusst im Package und halten die API für deine Commands so einfach wie möglich.

## Command Execution Logging

Alle Commands, die über das Web-Interface ausgeführt werden, werden automatisch in der Datenbank geloggt. Du kannst die Ausführungen in der Filament-Resource "Command Executions" einsehen.

### Status

Jede Command-Ausführung hat einen der folgenden Status:

- **`running`**: Der Command läuft gerade
- **`completed`**: Der Command wurde erfolgreich abgeschlossen
- **`failed`**: Der Command ist mit einem Fehler fehlgeschlagen
- **`cancelled`**: Der Command wurde vom Benutzer abgebrochen

### Gespeicherte Informationen

Für jede Ausführung werden folgende Daten gespeichert:

- **Basis-Informationen**: Command-Name, Beschreibung, Status, Zeitstempel
- **Steps**: Liste aller Steps, die ausgeführt wurden
- **Step-Outputs**: Output jedes einzelnen Steps (als JSON)
- **Context**: Alle Command-Properties (z.B. `$environment`, `$projectName`, etc.)
- **Fehler-Informationen**: Bei `failed` Status: Fehlermeldung und der Step, bei dem der Fehler aufgetreten ist (`failed_at_step`)
- **Abbruch-Informationen**: Bei `cancelled` Status: Der Step, bei dem abgebrochen wurde (`cancelled_at_step`)
- **Benutzer**: Polymorphe Beziehung zu dem Benutzer, der den Command gestartet hat (`created_by`)

### Migration ausführen

Um das Logging zu aktivieren, führe die Migration aus:

```bash
php artisan migrate
```

Die Migration erstellt die Tabelle `command_executions` mit allen notwendigen Feldern.

### Filament Resource

Die Filament-Resource "Command Executions" ist automatisch im Filament-Navigation-Menü verfügbar (falls aktiviert). Dort kannst du:

- Alle vergangenen Command-Ausführungen einsehen
- Nach Status filtern
- Details zu jeder Ausführung ansehen (Steps, Outputs, Context, etc.)
- Fehlgeschlagene oder abgebrochene Commands analysieren

Die Resource zeigt auch an, bei welchem Step ein Command fehlgeschlagen (`failed_at_step`) oder abgebrochen (`cancelled_at_step`) wurde.

## License

Siehe [LICENSE.md](LICENSE.md)
