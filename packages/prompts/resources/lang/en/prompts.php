<?php

return [
    'prompt' => 'Prompt',
    'prompts' => 'Prompts',

    'ui' => [
        'error_heading' => 'Error',
        'success_heading' => 'Command finished successfully!',
        'starting_heading' => 'Starting command...',
        'validation_title' => 'Please fix the following:',
        'next_button' => 'Next',
        'output_heading' => 'Command output',
        'confirm_yes' => 'Yes',
        'confirm_no' => 'No',
        'no_commands_available' => 'No commands available. Please configure allowed commands in',
        'command_label' => 'Command',
        'select_command_placeholder' => 'Please select a command …',
        'commands_config_hint' => 'Only commands from the configuration are visible here.',
        'start_command_button' => 'Start command',
        'back_to_selection' => 'Back to command selection',
        'unknown_error' => 'Unknown error',
        'navigation_label' => 'Command Runner',
        'navigation_group' => 'System',
    ],

    'errors' => [
        'command_not_found' => 'Command not found: :command',
        'step_not_found' => 'Step :step not found on command :class',
    ],

    'validation' => [
        'text_required' => 'Please fill in “:label”.',
        'multiselect_required' => 'Please select at least one option.',
        'multiselect_min' => 'Please select at least one option.',
        'select_required' => 'Please choose an option.',
        'select_in' => 'Please choose a valid option.',
        'callable_invalid' => 'Invalid value.',
    ],
];
