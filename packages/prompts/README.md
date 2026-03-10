# Moox Prompts

CLI- and Web-compatible prompts for Laravel Artisan commands – with a flow that can continue step-by-step in the browser.

## What does a Flow Command look like?

To make a command work as a flow in both CLI and Web, you only need to follow these rules:

- **Extend `FlowCommand`**  

  ```php
  use Moox\Prompts\Support\FlowCommand;
  use function Moox\Prompts\text;
  use function Moox\Prompts\select;
  ```

  ```php
  class ProjectSetupCommand extends FlowCommand
  {
      protected $signature = 'prompts:project-setup';
      protected $description = 'Project setup wizard (CLI & Web)';
  ```

- **Store state as public properties**  
  (they are automatically persisted between steps in the web flow)

  ```php
      public ?string $environment = null;
      public ?string $projectName = null;
  ```

- **Define steps via `promptFlowSteps()`** – the array order **is the flow order**

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

- **Each step is a `public function stepXyz(): void`** – ideally **one prompt per step**

  ```php
      public function stepIntro(): void
      {
          $this->info('=== Project Setup ===');
      }

      public function stepEnvironment(): void
      {
          $this->environment = select(
              label: 'Which environment do you want to configure?',
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
              label: 'What is your project name?',
              placeholder: 'e.g. MyCoolApp',
              validate: 'required|min:3',
              required: true,
          );
      }

      public function stepSummary(): void
      {
          $this->info('--- Summary ---');
          $this->line('Project: '.$this->projectName);
          $this->line('Environment: '.$this->environment);
      }
  }
  ```

- **Optional steps** can simply be skipped with a guard at the beginning:

  ```php
  public array $features = [];

  public function stepLoggingLevel(): void
  {
      if (! in_array('logging', $this->features, true)) {
          return; // skip step
      }

      // Prompt …
  }
  ```

- **Calling other Artisan commands** – in a flow, always use `$this->call()` instead of `Artisan::call()`, so the output is also visible in the web UI:

  ```php
  public function stepPublishConfig(): void
  {
      $shouldPublish = confirm(
          label: 'Publish the config now?',
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

That’s all you need in the command – no special flow methods, no custom persistence.  
Everything else (CLI/Web differences, state, web UI) is handled by the package.

## Running flows in the browser (Filament)

Once you’ve created a flow command, you can run it in both CLI and browser.

### CLI

```bash
php artisan prompts:project-setup
```

The command behaves like a normal Laravel Artisan command – all prompts are shown in the terminal.

### Web

1. Open the Filament page **“Run Command”** (automatically added to navigation)
2. Select your flow command from the list
3. Click **“Start command”**
4. The flow runs step by step in the browser:
   - Every step shows a prompt (text, select, multiselect, confirm, etc.)
   - After each step you see the step’s output
   - You can cancel any time with “Back to command selection”
   - After a successful run the button switches to “Start new command”

**Note:** All commands executed via the web UI are automatically logged in the database (see [Command Execution Logging](#command-execution-logging)).

## How and why reflection is used

If you’re just writing commands, you don’t need to care about reflection.  
To understand what happens under the hood, here’s a short overview.

- **Problem 1: Setting arguments & options in the web flow**  
  Laravel stores arguments/options internally on a protected `$input` property of your command.  
  In CLI mode the Artisan kernel takes care of this.  
  In the web flow, we create fresh command instances – and need to set `$input` ourselves.  
  That’s what `PromptFlowRunner::setCommandInput()` does via reflection:
  - finds the `input` property on your command object,
  - temporarily makes it accessible,
  - assigns the current `ArrayInput` instance.  
  **Result:** In flow commands you can keep using `argument()` and `option()` normally – both in CLI and in the browser.

- **Problem 2: Remembering command state between web requests**  
  In the web flow, your command runs across multiple HTTP requests. Without extra logic, properties like `$environment`, `$features`, `$projectName` would be lost between steps.  
  `PromptFlowRunner` handles this with two internal methods:
  - `captureCommandContext($command, $state)`  
    - uses reflection to read all non-static properties of your concrete command class  
    - stores simple values (scalars, arrays, `null`) into `PromptFlowState::$context`
  - `restoreCommandContext($command, $state)`  
    - restores all stored values back onto the new command instance on the next request  
  **Result:** For your code it feels like the same command instance keeps running – you don’t need your own persistence layer (cache, DB, session, …).

- **Problem 3: Initializing package tools in the web context**  
  Many packages using `Spatie\LaravelPackageTools` only register publishable resources (config, views, migrations, assets, …) in CLI context.  
  `WebCommandRunner` uses reflection to access the internal `package` object and replay `publishes(...)` registrations for the web context.  
  **Result:** Commands like `vendor:publish` work just as well in the browser as in CLI, even though Laravel is not running in console mode there.

**Important:**  
Reflection is only used inside the package internals, not in your flow commands.  
Your commands remain normal Laravel commands – you only need to:

- extend `FlowCommand`,
- define properties for your state,
- list steps in `promptFlowSteps()`,
- implement `step*` methods (ideally one prompt per step).

The package takes care of the rest (reflection, state, web flow).

### Are there alternatives without reflection?

Yes – technically we could avoid reflection, but it would degrade the DX:

- For **arguments & options** we’d need a custom API instead of `argument()/option()`, or force you to manage everything via properties/arrays. That’s less “Laravel-ish” and harder to learn.
- For **state between steps** we could ask you to manually list all properties to persist (e.g. `flowContextKeys()`), or manage cache/DB/session yourself. That’s more boilerplate and error-prone.
- For **Spatie Package Tools in the web** we’d either need changes in the Spatie package or manual configuration of all publishable paths – both would make setup more complex.

That’s why we intentionally keep reflection encapsulated in the package and keep your command API as simple as possible.

## Command Execution Logging

All commands executed via the web interface are automatically logged in the database.  
You can inspect them via the Filament resource **“Command Executions”**.

### Status

Each execution has one of the following statuses:

- **`running`**: The command is currently running
- **`completed`**: The command finished successfully
- **`failed`**: The command failed with an error
- **`cancelled`**: The command was cancelled by the user or aborted mid-flow

### Stored information

For each execution we store:

- **Basic information**: command name, description, status, timestamps
- **Steps**: ordered list of all defined steps
- **Step outputs**: output of each step (JSON)
- **Context**: all command properties (e.g. `$environment`, `$projectName`, `$features`, …)
- **Failure details**: for `failed` status – the error message and the step where it occurred (`failed_at_step`)
- **Cancellation details**: for `cancelled` status – the step where cancellation happened (`cancelled_at_step`)
- **User**: polymorphic relation to the user who started the command (`created_by`)

### Important: `step_outputs` vs `context`

It’s important to understand the difference between these two fields:

- **`step_outputs`**  
  - Contains the **console output** of each step.  
  - This is everything you print via `$this->info()`, `$this->line()`, `$this->warn()`, etc.  
  - Example:

    ```php
    public function stepEnvironment(): void
    {
        $this->environment = select(...);
        $this->info("✅ Environment: {$this->environment}");
    }
    ```

    will result in e.g.:

    ```json
    "stepEnvironment": "✅ Environment: production\n"
    ```

- **`context`**  
  - Contains the **raw state** of your command – all public, non-static properties from your concrete command class.  
  - This includes values returned from `text()`, `select()`, `multiselect()`, `confirm()`, etc.
  - Example:

    ```php
    public ?bool $publishConfig = null;

    public function stepPublishConfigConfirm(): void
    {
        $this->publishConfig = confirm(
            label: 'Publish the config now?',
            default: true,
        );
    }
    ```

    will store in `context` something like:

    ```json
    {
      "publishConfig": true
    }
    ```

    but `step_outputs["stepPublishConfigConfirm"]` will be an **empty string**, because `confirm()` itself doesn’t print anything.

If you want to see the user’s choice in the **step output** as well, you can explicitly print it:

```php
public function stepPublishConfigConfirm(): void
{
    $this->publishConfig = confirm(
        label: 'Publish the config now?',
        default: true,
    );

    $this->info('✅ Publish config: ' . ($this->publishConfig ? 'yes' : 'no'));
}
```

This way you have:

- the decision in `context.publishConfig`, and
- a readable line in `step_outputs.stepPublishConfigConfirm` for the history/inspector UI.

### Running the migration

To enable logging, run:

```bash
php artisan migrate
```

This creates the `command_executions` table with all necessary fields.

### Filament resource

The Filament resource **“Command Executions”** is automatically available in the Filament navigation (if enabled). There you can:

- inspect all past command executions,
- filter by status,
- see details per execution (steps, outputs, context, errors),
- analyze failed or cancelled commands.

The resource also shows which step a command **failed** on (`failed_at_step`) or where it was **cancelled** (`cancelled_at_step`).

## License

See [LICENSE.md](LICENSE.md)
