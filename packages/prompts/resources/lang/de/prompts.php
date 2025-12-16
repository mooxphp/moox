<?php

return [
    'prompt' => 'Prompt',
    'prompts' => 'Prompts',

    'ui' => [
        'error_heading' => 'Fehler',
        'success_heading' => 'Command erfolgreich abgeschlossen!',
        'starting_heading' => 'Command wird gestartet...',
        'validation_title' => 'Bitte korrigieren:',
        'next_button' => 'Weiter',
        'output_heading' => 'Command Ausgabe',
        'confirm_yes' => 'Ja',
        'confirm_no' => 'Nein',
        'no_commands_available' => 'Keine Commands verfügbar. Bitte konfiguriere die erlaubten Commands in der',
        'command_label' => 'Command',
        'select_command_placeholder' => 'Bitte Command auswählen …',
        'commands_config_hint' => 'Nur Commands aus der Konfiguration sind hier sichtbar.',
        'start_command_button' => 'Command starten',
        'back_to_selection' => 'Zurück zur Command-Auswahl',
        'unknown_error' => 'Unbekannter Fehler',
        'navigation_label' => 'Command Runner',
        'navigation_group' => 'System',
    ],

    'errors' => [
        'command_not_found' => 'Command nicht gefunden: :command',
        'step_not_found' => 'Step :step nicht gefunden auf Command :class',
    ],

    'validation' => [
        'text_required' => 'Bitte „:label“ ausfüllen.',
        'multiselect_required' => 'Bitte mindestens eine Option wählen.',
        'multiselect_min' => 'Bitte mindestens eine Option wählen.',
        'select_required' => 'Bitte wählen Sie eine Option aus.',
        'select_in' => 'Bitte wählen Sie eine gültige Option aus.',
        'callable_invalid' => 'Ungültiger Wert.',
    ],
];
