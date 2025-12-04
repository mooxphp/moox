<?php

namespace Moox\Prompts\Filament\Components;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;
use Moox\Prompts\Support\PendingPromptsException;
use Moox\Prompts\Support\PromptResponseStore;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Illuminate\Console\OutputStyle;

class RunCommandComponent extends Component implements HasForms
{
    use InteractsWithForms;

    public string $command = '';
    public array $commandInput = [];
    public ?array $currentPrompt = null;
    public string $output = '';
    public bool $isComplete = false;
    public ?string $error = null;
    public array $answers = [];
    public array $data = [];

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
        $this->error = null;
        
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
            $this->responseStore->resetCounter();
            
            foreach ($this->answers as $promptId => $answer) {
                $this->responseStore->set($promptId, $answer);
            }
            
            app()->instance('moox.prompts.response_store', $this->responseStore);

            $commandInstance = app(\Illuminate\Contracts\Console\Kernel::class)
                ->all()[$this->command] ?? null;

            if (!$commandInstance) {
                $this->error = "Command nicht gefunden: {$this->command}";
                return;
            }

            $commandInstance->setLaravel(app());
            $output = new BufferedOutput();

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

                $this->output = $output->fetch();
                $this->isComplete = true;
                $this->currentPrompt = null;
            } catch (PendingPromptsException $e) {
                $prompt = $e->getPrompt();
                $this->currentPrompt = $prompt;

                $promptId = $prompt['id'] ?? null;
                if ($promptId && isset($this->answers[$promptId])) {
                    $value = $this->answers[$promptId];
                    if ($prompt['method'] === 'multiselect') {
                        if (!is_array($value)) {
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
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->currentPrompt = null;
        }
    }

    public function submitPrompt(): void
    {
        if (!$this->currentPrompt) {
            return;
        }

        $promptId = $this->currentPrompt['id'] ?? null;
        if (!$promptId) {
            return;
        }

        try {
            if ($this->currentPrompt['method'] === 'multiselect') {
                $params = $this->currentPrompt['params'] ?? [];
                $options = $params[1] ?? [];
                $answer = [];
                
                foreach (array_keys($options) as $key) {
                    $checkboxId = $promptId . '_' . $key;
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
                    $updateKey = 'data.' . $promptId;
                    if (isset($updates[$updateKey])) {
                        $answer = $updates[$updateKey];
                    }
                    if ($answer === null && isset($updates[$promptId])) {
                        $answer = $updates[$promptId];
                    }
                    if ($answer === null) {
                        foreach ($updates as $key => $value) {
                            if (str_ends_with($key, '.' . $promptId) || $key === $promptId) {
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

            if (($answer === null || $answer === '' || ($this->currentPrompt['method'] === 'multiselect' && !is_array($answer))) && $this->currentPrompt['method'] !== 'confirm') {
                try {
                    $data = $this->form->getState();
                    $answer = $data[$promptId] ?? null;
                    
                    if ($this->currentPrompt['method'] === 'multiselect') {
                        if (!is_array($answer)) {
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
                    return;
                }
            }
            
            if (($answer === null || $answer === '') && $this->currentPrompt['method'] === 'select') {
                $params = $this->currentPrompt['params'] ?? [];
                $options = $params[1] ?? [];
                if (!empty($options)) {
                    $answer = array_key_first($options);
                }
            }
            
            if ($this->currentPrompt['method'] === 'confirm') {
                if ($answer === null) {
                    return;
                }
            } elseif ($answer === null || $answer === '') {
                return;
            }
            
            if ($this->currentPrompt['method'] === 'multiselect') {
                if (!is_array($answer)) {
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
                
                if (!is_array($answer)) {
                    $answer = [];
                }
            }
            
            if ($this->currentPrompt['method'] === 'confirm') {
                if (!is_bool($answer)) {
                    $answer = (bool) $answer;
                }
            }
            
            $this->answers[$promptId] = $answer;
            $this->currentPrompt = null;
            $this->runCommand();
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
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
        if ($this->isComplete || !$this->currentPrompt) {
            return [];
        }

        $promptId = $this->currentPrompt['id'] ?? 'prompt_0';
        $method = $this->currentPrompt['method'] ?? 'text';
        $params = $this->currentPrompt['params'] ?? [];

        if ($method === 'multiselect') {
            return $this->createMultiselectFields($promptId, $params);
        }

        $field = $this->createFieldFromPrompt($promptId, $method, $params);

        if (!$field) {
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
        
        $fields[] = Placeholder::make($promptId . '_label')
            ->label($label)
            ->content('');
        
        foreach ($options as $key => $optionLabel) {
            $checkboxId = $promptId . '_' . $key;
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
        $required = ($params[3] ?? false) !== false;
        $defaultValue = $this->answers[$promptId] ?? null;

        return match($method) {
            'text' => TextInput::make($promptId)
                ->label($label)
                ->placeholder($params[1] ?? '')
                ->default($defaultValue ?? $params[2] ?? '')
                ->required($required)
                ->hint($params[6] ?? null)
                ->live(onBlur: false),

            'textarea' => Textarea::make($promptId)
                ->label($label)
                ->placeholder($params[1] ?? '')
                ->default($defaultValue ?? $params[2] ?? '')
                ->required($required)
                ->rows(5)
                ->hint($params[6] ?? null),

            'password' => TextInput::make($promptId)
                ->label($label)
                ->password()
                ->placeholder($params[1] ?? '')
                ->default($defaultValue ?? '')
                ->required($required)
                ->hint($params[6] ?? null)
                ->live(onBlur: false),

            'select' => Select::make($promptId)
                ->label($label)
                ->options($params[1] ?? [])
                ->default($defaultValue ?? $params[2] ?? null)
                ->required($required)
                ->hint($params[4] ?? null)
                ->live(onBlur: false),

            'multiselect' => null,

            'confirm' => Radio::make($promptId)
                ->label($label)
                ->options([
                    true => 'Ja',
                    false => 'Nein',
                ])
                ->default($defaultValue ?? $params[1] ?? false)
                ->required($required)
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
