<?php

namespace Moox\Prompts\Filament\Components;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Moox\Prompts\Support\PendingPromptsException;
use Moox\Prompts\Support\PromptResponseStore;
use Moox\Prompts\Support\WebCommandRunner;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class RunCommandComponent extends Component implements HasForms
{
    use InteractsWithForms;

    public string $command = '';

    public array $commandInput = [];

    public ?array $currentPrompt = null;

    public string $output = '';

    public string $currentStepOutput = '';

    public string $lastOutput = '';

    public bool $isComplete = false;

    public array $validationErrors = [];

    public ?string $error = null;

    public array $answers = [];

    public array $data = [];

    public int $executionStep = 0;

    protected PromptResponseStore $responseStore;

    public function boot(): void
    {
        $this->responseStore = new PromptResponseStore;
        app()->instance('moox.prompts.response_store', $this->responseStore);
    }

    public function mount(string $command = '', array $commandInput = []): void
    {
        $this->command = $command;
        $this->commandInput = $commandInput;
        $this->answers = [];
        $this->isComplete = false;
        $this->currentPrompt = null;
        $this->output = '';
        $this->currentStepOutput = '';
        $this->lastOutput = '';
        $this->validationErrors = [];
        $this->executionOutputHashes = [];
        $this->error = null;
        $this->commandStarted = false;
        $this->executionStep = 0;

        $this->responseStore->resetCounter();

        if ($command) {
            $this->runCommand();
        }
    }

    protected function runCommand(): void
    {
        $this->error = null;
        $this->isComplete = false;

        try {
            // Stelle sicher, dass publishable Resources registriert sind (auch im Web-Kontext)
            // Dies löst das Problem, dass Spatie Package Tools nur registriert, wenn runningInConsole() true ist
            WebCommandRunner::ensurePublishableResourcesRegistered();
            
            $this->responseStore->resetCounter();

            foreach ($this->answers as $promptId => $answer) {
                $this->responseStore->set($promptId, $answer);
            }

            foreach ($this->answers as $promptId => $answer) {
                $this->responseStore->set($promptId, $answer);
            }

            app()->instance('moox.prompts.response_store', $this->responseStore);

            $commandInstance = app(\Illuminate\Contracts\Console\Kernel::class)
                ->all()[$this->command] ?? null;

            if (! $commandInstance) {
                $this->error = "Command nicht gefunden: {$this->command}";

                return;
            }

            $commandInstance->setLaravel(app());
            $output = new BufferedOutput;

            $outputStyle = new OutputStyle(
                new ArrayInput($this->commandInput),
                $output
            );
            $commandInstance->setOutput($outputStyle);

            try {
                $commandInstance->run(
                    new ArrayInput($this->commandInput),
                    $output
                );

                $newOutput = $output->fetch();
                // Kumulativer Output: Füge neuen Output zum bestehenden hinzu
                if (! empty($newOutput)) {
                    if (! empty($this->output)) {
                        $this->output .= "\n" . $newOutput;
                    } else {
                        $this->output = $newOutput;
                    }
                }
                // Am Ende: Zeige den vollständigen Output
                $this->currentStepOutput = $this->output;
                $this->isComplete = true;
                $this->currentPrompt = null;
            } catch (PendingPromptsException $e) {
                $newOutput = $output->fetch();
                // Kumulativer Output: Füge neuen Output zum bestehenden hinzu
                if (! empty($newOutput)) {
                    if (! empty($this->output)) {
                        $this->output .= "\n" . $newOutput;
                    } else {
                        $this->output = $newOutput;
                    }
                }
                // Zeige kumulativen Output während Prompts für Debugging
                $this->currentStepOutput = $this->output ?? '';
                
                $prompt = $e->getPrompt();
                $this->currentPrompt = $prompt;
                $this->executionStep++;

                $promptId = $prompt['id'] ?? null;
                if ($promptId && isset($this->answers[$promptId])) {
                    $value = $this->answers[$promptId];
                    if ($prompt['method'] === 'multiselect') {
                        if (! is_array($value)) {
                            if ($value === true) {
                                $params = $prompt['params'] ?? [];
                                $options = $params[1] ?? [];
                                $value = array_keys($options);
                            } else {
                                $value = [];
                            }
                        }
                    }
                    $this->form->fill([$promptId => $value]);
                }
            }
        } catch (Throwable $e) {
            $newOutput = isset($output) ? $output->fetch() : '';
            // Kumulativer Output auch bei Exceptions
            if (! empty($newOutput)) {
                if (! empty($this->output)) {
                    $this->output .= "\n" . $newOutput;
                } else {
                    $this->output = $newOutput;
                }
            }
            $this->output = $this->appendExceptionToOutput($this->output, $e);
            $this->currentStepOutput = $this->output;
            $this->error = $this->formatThrowableMessage($e);
            $this->currentPrompt = null;
        }
    }

    protected function formatThrowableMessage(Throwable $e): string
    {
        return sprintf(
            '%s: %s in %s:%d',
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
    }

    protected function appendExceptionToOutput(string $output, Throwable $e): string
    {
        $trace = $e->getTraceAsString();

        return trim($output."\n\n".$this->formatThrowableMessage($e)."\n".$trace);
    }

    public function submitPrompt(): void
    {
        if (! $this->currentPrompt) {
            return;
        }

        $promptId = $this->currentPrompt['id'] ?? null;
        if (! $promptId) {
            return;
        }

        try {
            $this->validationErrors = [];

            if ($this->currentPrompt['method'] === 'multiselect') {
                $params = $this->currentPrompt['params'] ?? [];
                $options = $params[1] ?? [];
                $answer = [];

                foreach (array_keys($options) as $key) {
                    $checkboxId = $promptId.'_'.$key;
                    if (isset($this->data[$checkboxId]) && $this->data[$checkboxId] === true) {
                        $answer[] = $key;
                    }
                }
            } else {
                $answer = $this->data[$promptId] ?? null;
            }

            if ($this->currentPrompt['method'] !== 'multiselect' && ($answer === null || ($answer === '' && $this->currentPrompt['method'] !== 'confirm'))) {
                $allRequestData = request()->all();
                $updates = data_get($allRequestData, 'components.0.updates', []);

                if (is_array($updates) || is_object($updates)) {
                    $updateKey = 'data.'.$promptId;
                    if (isset($updates[$updateKey])) {
                        $answer = $updates[$updateKey];
                    }
                    if ($answer === null && isset($updates[$promptId])) {
                        $answer = $updates[$promptId];
                    }
                    if ($answer === null) {
                        foreach ($updates as $key => $value) {
                            if (str_ends_with($key, '.'.$promptId) || $key === $promptId) {
                                $answer = $value;
                                break;
                            }
                        }
                    }
                }
            }

            if ($this->currentPrompt['method'] !== 'multiselect' && ($answer === null || ($answer === '' && $this->currentPrompt['method'] !== 'confirm'))) {
                $rawState = $this->form->getRawState();
                $answer = $rawState[$promptId] ?? null;
            }

            if ($this->currentPrompt['method'] === 'confirm' && $answer === null) {
                $answer = false;
            }

            if (($answer === null || $answer === '' || ($this->currentPrompt['method'] === 'multiselect' && ! is_array($answer))) && $this->currentPrompt['method'] !== 'confirm') {
                try {
                    $data = $this->form->getState();
                    $answer = $data[$promptId] ?? null;

                    if ($this->currentPrompt['method'] === 'multiselect') {
                        if (! is_array($answer)) {
                            if ($answer === true) {
                                $params = $this->currentPrompt['params'] ?? [];
                                $options = $params[1] ?? [];
                                $answer = array_keys($options);
                            } else {
                                $answer = [];
                            }
                        }
                    }
                } catch (\Illuminate\Validation\ValidationException $e) {
                    // Capture Filament validation errors
                    $errors = $e->errors();
                    $this->validationErrors = [];
                    
                    // Get errors for current prompt field
                    if (isset($errors[$promptId])) {
                        $this->validationErrors = is_array($errors[$promptId]) 
                            ? $errors[$promptId] 
                            : [$errors[$promptId]];
                    }
                    
                    // If no specific field errors, get all errors
                    if (empty($this->validationErrors)) {
                        foreach ($errors as $fieldErrors) {
                            if (is_array($fieldErrors)) {
                                $this->validationErrors = array_merge($this->validationErrors, $fieldErrors);
                            } else {
                                $this->validationErrors[] = $fieldErrors;
                            }
                        }
                    }
                    
                    return;
                }
            }

            // Validate empty fields before returning
            if ($this->currentPrompt['method'] === 'confirm') {
                if ($answer === null) {
                    $this->validatePromptAnswer($promptId, null, $this->currentPrompt);
                    return;
                }
            } elseif ($this->currentPrompt['method'] === 'select') {
                // Für Select: Prüfe ob leer oder null
                if ($answer === null || $answer === '' || $answer === '0') {
                    $this->validatePromptAnswer($promptId, $answer, $this->currentPrompt);
                    return;
                }
            } elseif ($answer === null || $answer === '') {
                $this->validatePromptAnswer($promptId, '', $this->currentPrompt);
                return;
            }

            if ($this->currentPrompt['method'] === 'multiselect') {
                if (! is_array($answer)) {
                    if ($answer === true) {
                        $params = $this->currentPrompt['params'] ?? [];
                        $options = $params[1] ?? [];
                        $answer = array_keys($options);
                    } elseif ($answer !== null && $answer !== '') {
                        $answer = [$answer];
                    } else {
                        $answer = [];
                    }
                }

                if (! is_array($answer)) {
                    $answer = [];
                }
            }

            if ($this->currentPrompt['method'] === 'confirm') {
                if (! is_bool($answer)) {
                    $answer = (bool) $answer;
                }
            }

            $this->validatePromptAnswer($promptId, $answer, $this->currentPrompt);
            if (! empty($this->validationErrors)) {
                return;
            }

            $this->answers[$promptId] = $answer;
            $this->currentPrompt = null;
            $this->runCommand();
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    protected function validatePromptAnswer(string $promptId, mixed $answer, array $prompt): void
    {
        $method = $prompt['method'] ?? '';
        $params = $prompt['params'] ?? [];

        $rules = [];
        $messages = [];

        // Map required flag per method (parameter positions differ)
        $requiredFlag = match ($method) {
            'text', 'textarea', 'password' => $params[3] ?? false,
            'multiselect' => $params[3] ?? false,
            'confirm' => $params[2] ?? false,
            default => false,
        };

        // Multiselect: erzwinge Auswahl, wenn Optionen vorhanden
        if ($method === 'multiselect' && ! empty($params[1] ?? [])) {
            $requiredFlag = true;
        }

        // Map validate parameter per method (avoid treating confirm/labels as rules)
        $validate = match ($method) {
            'text', 'textarea', 'password' => $params[4] ?? null,
            'select' => $params[5] ?? null,
            'multiselect' => $params[6] ?? null,
            default => null,
        };

        // Normalize rule strings (split pipe-delimited)
        $pushRules = function (array &$into, string|array|null $value): void {
            if ($value === null || $value === false || $value === '') {
                return;
            }
            $items = is_array($value) ? $value : explode('|', $value);
            foreach ($items as $item) {
                $item = trim((string) $item);
                if ($item !== '') {
                    $into[] = $item;
                }
            }
        };

        $pushRules($rules, $requiredFlag ? 'required' : null);

        if ($method === 'multiselect') {
            $rules[] = 'array';
            if ($requiredFlag !== false) {
                $rules[] = 'min:1';
            }
        }

        if ($method === 'confirm') {
            $rules[] = 'boolean';
        }

        if ($method === 'select' && $requiredFlag !== false) {
            $rules[] = 'in:'.implode(',', array_keys($params[1] ?? []));
        }

        $pushRules($rules, $validate);

        $callableErrors = [];
        $validateCallable = null;
        if (is_callable($validate)) {
            $validateCallable = $validate;
        } elseif (is_array($validate)) {
            foreach ($validate as $item) {
                if (is_callable($item)) {
                    $validateCallable = $item;
                    break;
                }
            }
        }

        if ($validateCallable) {
            $result = $validateCallable($answer);
            if (is_string($result) && $result !== '') {
                $callableErrors[] = $result;
            }
            if ($result === false) {
                $callableErrors[] = 'Ungültiger Wert.';
            }
        }

        if (! empty($rules)) {
            // Freundlichere Meldungen speziell für Multiselect
            if ($method === 'multiselect') {
                $messages["{$promptId}.required"] = 'Bitte mindestens eine Option wählen.';
                $messages["{$promptId}.min"] = 'Bitte mindestens eine Option wählen.';
            }
            
            // Freundlichere Meldungen speziell für Select
            if ($method === 'select') {
                $messages["{$promptId}.required"] = 'Bitte wählen Sie eine Option aus.';
                $messages["{$promptId}.in"] = 'Bitte wählen Sie eine gültige Option aus.';
            }

            $validator = Validator::make(
                [$promptId => $answer],
                [$promptId => $rules],
                $messages
            );

            if ($validator->fails()) {
                $this->validationErrors = $validator->errors()->all();
            }
        }

        if (! empty($callableErrors)) {
            $this->validationErrors = array_merge($this->validationErrors, $callableErrors);
        }
    }

    protected function getForms(): array
    {
        return ['form'];
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components($this->getFormSchema())
            ->statePath('data');
    }

    protected function getFormSchema(): array
    {
        if ($this->isComplete || ! $this->currentPrompt) {
            return [];
        }

        $promptId = $this->currentPrompt['id'] ?? 'prompt_0';
        $method = $this->currentPrompt['method'] ?? 'text';
        $params = $this->currentPrompt['params'] ?? [];

        if ($method === 'multiselect') {
            return $this->createMultiselectFields($promptId, $params);
        }

        $field = $this->createFieldFromPrompt($promptId, $method, $params);

        if (! $field) {
            return [];
        }

        return [$field];
    }

    protected function createMultiselectFields(string $promptId, array $params): array
    {
        $label = $params[0] ?? '';
        $required = ($params[3] ?? false) !== false;
        $defaultValue = $this->answers[$promptId] ?? null;
        $options = $params[1] ?? [];

        $fields = [];

        $fields[] = Placeholder::make($promptId.'_label')
            ->label($label)
            ->content('');

        foreach ($options as $key => $optionLabel) {
            $checkboxId = $promptId.'_'.$key;
            $isChecked = is_array($defaultValue) && in_array($key, $defaultValue);

            $fields[] = Checkbox::make($checkboxId)
                ->label($optionLabel)
                ->default($isChecked)
                ->live(onBlur: false);
        }

        return $fields;
    }

    protected function createFieldFromPrompt(string $promptId, string $method, array $params): ?\Filament\Forms\Components\Field
    {
        $label = $params[0] ?? '';
        // Determine required flag per prompt type (indexes differ)
        $required = match ($method) {
            'text', 'textarea', 'password' => ($params[3] ?? false) !== false,
            'multiselect' => ($params[3] ?? false) !== false,
            'confirm' => ($params[2] ?? false) !== false,
            'select' => ($params[2] ?? null) === null, // Required wenn kein Default gesetzt
            default => false,
        };
        $defaultValue = $this->answers[$promptId] ?? null;
        $options = $params[1] ?? [];
        $defaultSelect = $defaultValue ?? ($params[2] ?? null);
        $confirmDefault = $defaultValue ?? ($params[1] ?? false);

        // Map validate parameter per method
        $validate = match ($method) {
            'text', 'textarea', 'password' => $params[4] ?? null,
            'select' => $params[5] ?? null,
            'multiselect' => $params[6] ?? null,
            default => null,
        };

        // Build validation rules for Filament fields (normalize pipe-delimited)
        $rules = [];
        $pushRules = function (array &$into, string|array|null $value): void {
            if ($value === null || $value === false || $value === '') {
                return;
            }
            $items = is_array($value) ? $value : explode('|', $value);
            foreach ($items as $item) {
                $item = trim((string) $item);
                if ($item !== '') {
                    $into[] = $item;
                }
            }
        };

        if ($required) {
            $rules[] = 'required';
        }
        
        // Add method-specific rules
        if ($method === 'select' && $required && ! empty($options)) {
            $rules[] = 'in:'.implode(',', array_keys($options));
        }
        
        if ($method === 'confirm') {
            $rules[] = 'boolean';
        }
        
        $pushRules($rules, $validate);

        // Kein automatisches Default für Select - Validation wird verwendet

        return match ($method) {
            'text' => TextInput::make($promptId)
                ->label($label)
                ->placeholder($params[1] ?? '')
                ->default($defaultValue ?? $params[2] ?? '')
                ->rules($rules)
                ->hint($params[6] ?? null)
                ->live(onBlur: false),

            'textarea' => Textarea::make($promptId)
                ->label($label)
                ->placeholder($params[1] ?? '')
                ->default($defaultValue ?? $params[2] ?? '')
                ->rules($rules)
                ->rows(5)
                ->hint($params[6] ?? null),

            'password' => TextInput::make($promptId)
                ->label($label)
                ->password()
                ->placeholder($params[1] ?? '')
                ->default($defaultValue ?? '')
                ->rules($rules)
                ->hint($params[6] ?? null)
                ->live(onBlur: false),

            'select' => Select::make($promptId)
                ->label($label)
                ->options($options)
                ->default($defaultSelect !== null ? $defaultSelect : null)
                ->rules($rules)
                ->placeholder('Bitte wählen...')
                ->hint($params[4] ?? null)
                ->live(onBlur: false),

            'multiselect' => null,

            'confirm' => Radio::make($promptId)
                ->label($label)
                ->options([
                    true => 'Ja',
                    false => 'Nein',
                ])
                ->default($confirmDefault)
                ->rules($rules)
                ->hint($params[6] ?? null)
                ->live(onBlur: false),

            default => null,
        };
    }

    public function render()
    {
        return view('moox-prompts::filament.components.run-command');
    }
}
