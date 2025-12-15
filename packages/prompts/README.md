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

## License

Siehe [LICENSE.md](LICENSE.md)
