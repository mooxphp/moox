<div>
    @if($error)
        <x-filament::section>
            <x-slot name="heading">
                {{ __('moox-prompts::prompts.ui.error_heading') }}
            </x-slot>
            <p style="color: #b91c1c;">{{ $error }}</p>
            @if($output)
                <pre
                    style="background-color: #111827; color: #f87171; padding: 1rem; border-radius: 0.25rem; overflow: auto; font-size: 0.875rem; margin-top: 0.75rem;">{{ $output }}</pre>
            @elseif($currentStepOutput)
                <pre
                    style="background-color: #111827; color: #f87171; padding: 1rem; border-radius: 0.25rem; overflow: auto; font-size: 0.875rem; margin-top: 0.75rem;">{{ $currentStepOutput }}</pre>
            @endif
        </x-filament::section>
    @elseif($isComplete)
        <x-filament::section x-data x-init="$dispatch('command-completed')">
            <x-slot name="heading">
                {{ __('moox-prompts::prompts.ui.success_heading') }}
            </x-slot>
            @if($output)
                <pre
                    style="background-color: #111827; color: #4ade80; padding: 1rem; border-radius: 0.25rem; overflow: auto; font-size: 0.875rem;">{{ $output }}</pre>
            @endif
        </x-filament::section>
    @elseif($currentPrompt)
        <div x-data="{ handleEnter(event) { if (event.target.tagName !== 'TEXTAREA') { $wire.submitPrompt(); } } }"
            @keydown.enter.prevent="handleEnter($event)">
            {{ $this->form }}

            @if(!empty($validationErrors))
                <div
                    style="margin-top: 1rem; padding: 0.75rem 1rem; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 0.375rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <svg style="width: 1.25rem; height: 1.25rem; color: #f59e0b; flex-shrink: 0;" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                        <strong style="color: #92400e; font-size: 0.875rem; font-weight: 600;">
                            {{ __('moox-prompts::prompts.ui.validation_title') }}
                        </strong>
                    </div>
                    <ul style="color: #92400e; margin: 0; padding-left: 1.25rem; list-style: disc; font-size: 0.875rem;">
                        @foreach($validationErrors as $msg)
                            <li>{{ $msg }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="margin-top: 1rem; display: flex; justify-content: flex-end;">
                <x-filament::button wire:click="submitPrompt" type="button" color="primary">
                    {{ __('moox-prompts::prompts.ui.next_button') }}
                </x-filament::button>
            </div>

            @if($currentStepOutput)
                <x-filament::section style="margin-top: 1rem;">
                    <x-slot name="heading">
                        {{ __('moox-prompts::prompts.ui.output_heading') }}
                    </x-slot>
                    <pre
                        style="background-color: #111827; color: #4ade80; padding: 1rem; border-radius: 0.25rem; overflow: auto; font-size: 0.875rem; max-height: 400px;">{{ $currentStepOutput }}</pre>
                </x-filament::section>
            @endif
        </div>
    @else
        <x-filament::section>
            <x-slot name="heading">
                {{ __('moox-prompts::prompts.ui.starting_heading') }}
            </x-slot>
            <x-filament::loading-indicator />
        </x-filament::section>
    @endif

    {{-- Buttons inside component so cancel() runs first and saves cancelled state before page resets --}}
    @if($flowId && !$isComplete && !$error)
        <div style="margin-top: 1rem;">
            <x-filament::button wire:click="cancel" type="button" color="warning">
                {{ __('moox-prompts::prompts.ui.back_to_selection') }}
            </x-filament::button>
        </div>
    @endif
    @if($isComplete)
        <div style="margin-top: 1rem;" x-data x-on:click="$dispatch('prompts-flow-cancelled')">
            <x-filament::button type="button" color="primary">
                {{ __('moox-prompts::prompts.ui.start_new_command') }}
            </x-filament::button>
        </div>
    @endif
</div>