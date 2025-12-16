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
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Moox\Prompts\Support\PromptFlowRunner;
use Moox\Prompts\Support\PromptFlowStateStore;
use Moox\Prompts\Support\PromptParamsHelper;
use Moox\Prompts\Support\PromptResponseStore;
use Moox\Prompts\Support\PromptRuntime;
use Moox\Prompts\Support\WebCommandRunner;
use Moox\Prompts\Support\WebPromptRuntime;
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

    public ?string $flowId = null;

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
        $this->flowId = null;

        $this->responseStore->clear();

        if ($command) {
            $this->runCommand();
        }
    }

    protected function runCommand(): void
    {
        $this->error = null;
        $this->isComplete = false;

        try {
            // erzwinge Web-Runtime (nicht CLI) im Web-Kontext
            app()->instance(PromptRuntime::class, new WebPromptRuntime);
            WebCommandRunner::ensurePublishableResourcesRegistered();

            $runner = app(PromptFlowRunner::class);
            $stateStore = app(PromptFlowStateStore::class);

            $state = $this->flowId ? $stateStore->get($this->flowId) : null;
            if (! $state) {
                // frischer Flow: ResponseStore und lokale States leeren
                $this->responseStore->clear();
                $this->responseStore->resetCounter();
                $this->answers = [];
                $this->data = [];
                $this->currentPrompt = null;
                $this->isComplete = false;
                $this->error = null;
                $this->output = '';
                $this->currentStepOutput = '';
                $state = $runner->start($this->command, $this->commandInput);
                $this->flowId = $state->flowId;
            }

            // Antworten in den ResponseStore spiegeln (ohne den Zähler zu manipulieren)
            foreach ($this->answers as $promptId => $answer) {
                $this->responseStore->set($promptId, $answer);
            }

            app()->instance('moox.prompts.response_store', $this->responseStore);

            while (true) {
                $result = $runner->runNext($state, $this->commandInput, $this->responseStore);
                $this->appendOutput($result['output'] ?? '');
                $state = $result['state'];

                if (! empty($result['prompt'])) {
                    $this->currentStepOutput = $this->output ?? '';
                    $this->currentPrompt = $result['prompt'];
                    $this->executionStep++;
                    $this->prefillPromptForm($result['prompt']);

                    return;
                }

                if (! empty($result['failed'])) {
                    $this->currentStepOutput = $this->output;
                    $this->error = $result['error'] ?? 'Unbekannter Fehler';
                    $this->currentPrompt = null;

                    return;
                }

                if (! empty($result['completed'])) {
                    $this->currentStepOutput = $this->output;
                    $this->isComplete = true;
                    $this->currentPrompt = null;
                    $this->responseStore->clear();
                    $this->responseStore->resetCounter();
                    $this->answers = [];
                    $this->data = [];
                    $this->flowId = null;

                    return;
                }
            }
        } catch (Throwable $e) {
            $this->output = $this->appendExceptionToOutput($this->output, $e);
            $this->currentStepOutput = $this->output;
            $this->error = $this->formatThrowableMessage($e);
            $this->currentPrompt = null;
        }
    }

    protected function appendOutput(?string $newOutput): void
    {
        if (empty($newOutput)) {
            return;
        }

        if (! empty($this->output)) {
            $this->output .= "\n".$newOutput;
        } else {
            $this->output = $newOutput;
        }
    }

    protected function prefillPromptForm(array $prompt): void
    {
        $promptId = $prompt['id'] ?? null;
        if (! $promptId) {
            return;
        }

        $method = $prompt['method'] ?? '';
        $params = $prompt['params'] ?? [];
        $p = PromptParamsHelper::extract($method, $params);

        // Wenn bereits eine Antwort vorhanden ist, diese verwenden
        if (isset($this->answers[$promptId])) {
            $value = $this->answers[$promptId];
            if ($method === 'multiselect') {
                if (! is_array($value)) {
                    if ($value === true) {
                        $options = $p['options'] ?? [];
                        $value = array_keys($options);
                    } else {
                        $value = [];
                    }
                }
            }
            $this->form->fill([$promptId => $value]);

            return;
        }

        // Ansonsten Default-Wert aus den Prompt-Params verwenden
        if ($method === 'confirm') {
            $default = $p['default'] ?? false; // default Parameter (bool)
            $value = $default ? 'yes' : 'no';
            $this->form->fill([$promptId => $value]);

            return;
        }

        if ($method === 'multiselect') {
            $defaultValue = $p['default'] ?? []; // default Parameter (array)
            // Für multiselect müssen wir die einzelnen Checkboxen füllen
            $options = $p['options'] ?? [];
            $fillData = [];
            foreach (array_keys($options) as $key) {
                $checkboxId = $promptId.'_'.$key;
                $fillData[$checkboxId] = is_array($defaultValue) && in_array($key, $defaultValue);
            }
            if (! empty($fillData)) {
                $this->form->fill($fillData);
            }

            return;
        }

        $defaultValue = null;
        if ($method === 'select') {
            $defaultValue = $p['default'] ?? null; // default Parameter
        } elseif (in_array($method, ['text', 'textarea', 'password'])) {
            $defaultValue = $p['default'] ?? ''; // default Parameter
        }

        if ($defaultValue !== null) {
            $this->form->fill([$promptId => $defaultValue]);
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
                    $errors = $e->errors();
                    $this->validationErrors = [];

                    if (isset($errors[$promptId])) {
                        $this->validationErrors = is_array($errors[$promptId])
                            ? $errors[$promptId]
                            : [$errors[$promptId]];
                    }

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

            if ($this->currentPrompt['method'] === 'confirm') {
                if ($answer === null) {
                    $this->validatePromptAnswer($promptId, null, $this->currentPrompt);

                    return;
                }
            } elseif ($this->currentPrompt['method'] === 'select') {
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
                if ($answer === 'yes') {
                    $answer = true;
                } elseif ($answer === 'no') {
                    $answer = false;
                } elseif (! is_bool($answer)) {
                    $answer = (bool) $answer;
                }
            }

            $this->validatePromptAnswer($promptId, $answer, $this->currentPrompt);
            if (! empty($this->validationErrors)) {
                return;
            }

            $this->answers[$promptId] = $answer;
            $this->responseStore->set($promptId, $answer);
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

        $p = PromptParamsHelper::extract($method, $params);

        $rules = [];
        $messages = [];

        $requiredFlag = match ($method) {
            'text', 'textarea', 'password' => $p['required'] ?? false,
            'multiselect' => $p['required'] ?? false,
            'confirm' => $p['required'] ?? false,
            default => false,
        };

        if ($method === 'multiselect' && ! empty($p['options'] ?? [])) {
            $requiredFlag = true;
        }

        $validate = match ($method) {
            'text', 'textarea', 'password' => $p['validate'] ?? null,
            'select' => $p['validate'] ?? null,
            'multiselect' => $p['validate'] ?? null,
            default => null,
        };

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

        if ($method === 'confirm' && $requiredFlag !== false) {
            $rules[] = 'required';
        }

        if ($method === 'select' && $requiredFlag !== false) {
            $rules[] = 'in:'.implode(',', array_keys($p['options'] ?? []));
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
                $callableErrors[] = __('moox-prompts::prompts.validation.callable_invalid');
            }
        }

        if (! empty($rules)) {
            $label = $p['label'] ?? $promptId;

            if (in_array($method, ['text', 'textarea', 'password'])) {
                $messages["{$promptId}.required"] = __('moox-prompts::prompts.validation.text_required', ['label' => $label]);
            }

            if ($method === 'multiselect') {
                $messages["{$promptId}.required"] = __('moox-prompts::prompts.validation.multiselect_required');
                $messages["{$promptId}.min"] = __('moox-prompts::prompts.validation.multiselect_min');
            }

            if ($method === 'select') {
                $messages["{$promptId}.required"] = __('moox-prompts::prompts.validation.select_required');
                $messages["{$promptId}.in"] = __('moox-prompts::prompts.validation.select_in');
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
        $p = PromptParamsHelper::extract('multiselect', $params);

        $label = $p['label'] ?? '';
        $required = ($p['required'] ?? false) !== false;
        // Default-Wert: erst aus answers, dann aus default-Parameter
        $defaultValue = $this->answers[$promptId] ?? ($p['default'] ?? []);
        $options = $p['options'] ?? [];

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
        $p = PromptParamsHelper::extract($method, $params);

        $label = $p['label'] ?? '';
        $required = match ($method) {
            'text', 'textarea', 'password' => ($p['required'] ?? false) !== false,
            'multiselect' => ($p['required'] ?? false) !== false,
            'confirm' => ($p['required'] ?? false) !== false,
            'select' => ($p['default'] ?? null) === null,
            default => false,
        };
        $defaultValue = $this->answers[$promptId] ?? null;
        $options = $p['options'] ?? [];
        $defaultSelect = $defaultValue ?? ($p['default'] ?? null);

        // Für confirm: Default aus params[1] (default Parameter), falls noch keine Antwort vorhanden
        $confirmDefault = null;
        if ($method === 'confirm') {
            $confirmDefault = $defaultValue !== null ? $defaultValue : ($p['default'] ?? false);
        }

        $validate = match ($method) {
            'text', 'textarea', 'password' => $p['validate'] ?? null,
            'select' => $p['validate'] ?? null,
            'multiselect' => $p['validate'] ?? null,
            default => null,
        };

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

        if ($method === 'select' && $required && ! empty($options)) {
            $rules[] = 'in:'.implode(',', array_keys($options));
        }

        $pushRules($rules, $validate);

        return match ($method) {
            'text' => TextInput::make($promptId)
                ->label($label)
                ->placeholder($p['placeholder'] ?? '')
                ->default($defaultValue ?? $p['default'] ?? '')
                ->rules($rules)
                ->hint($p['hint'] ?? null)
                ->live(onBlur: false),

            'textarea' => Textarea::make($promptId)
                ->label($label)
                ->placeholder($p['placeholder'] ?? '')
                ->default($defaultValue ?? $p['default'] ?? '')
                ->rules($rules)
                ->rows(5)
                ->hint($p['hint'] ?? null),

            'password' => TextInput::make($promptId)
                ->label($label)
                ->password()
                ->placeholder($p['placeholder'] ?? '')
                ->default($defaultValue ?? '')
                ->rules($rules)
                ->hint($p['hint'] ?? null)
                ->live(onBlur: false),

            'select' => Select::make($promptId)
                ->label($label)
                ->options($options)
                ->default($defaultSelect !== null ? $defaultSelect : null)
                ->rules($rules)
                ->selectablePlaceholder(false)
                ->hint($p['hint'] ?? null)
                ->live(onBlur: false),

            'multiselect' => null,

            'confirm' => Radio::make($promptId)
                ->label($label)
                ->options([
                    'yes' => 'Ja',
                    'no' => 'Nein',
                ])
                ->default($confirmDefault !== null ? ($confirmDefault ? 'yes' : 'no') : null)
                ->rules($rules)
                ->hint($p['hint'] ?? null)
                ->live(onBlur: false),

            default => null,
        };
    }

    public function render()
    {
        return view('moox-prompts::filament.components.run-command');
    }
}
