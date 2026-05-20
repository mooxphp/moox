<?php

return [
    'single' => 'Device',
    'plural' => 'Devices',
    'breadcrumb' => 'Device',
    'title' => 'Device',
    'navigation_label' => 'User Device',
    'navigation_group' => 'Moox User',
    'created_at' => 'Created at',
    'active' => 'Active',

    // Mail
    'mail_subject_new_device' => 'Neuer Geräte-Login erkannt',
    'mail_title_new_device' => 'Neues Gerät registriert',
    'mail_greeting' => 'Hallo',
    'mail_intro' => 'Wir haben einen neuen Login in deinem Account erkannt.',
    'mail_label_device' => 'Gerät',
    'mail_label_system' => 'System',
    'mail_label_ip' => 'IP-Adresse',
    'mail_label_location' => 'Ort',
    'mail_if_it_was_you' => 'Wenn du das selbst warst, kannst du diese E‑Mail ignorieren.',
    'mail_if_it_was_not_you' => 'Wenn du das nicht warst:',
    'mail_step_review_devices' => 'prüfe deine Geräte‑Liste',
    'mail_step_change_password' => 'ändere dein Passwort',
    'mail_step_check_mfa' => 'aktiviere (oder prüfe) Multi‑Faktor‑Authentifizierung',
    'mail_cta_trust_device' => 'Dieses Gerät bestätigen',
    'mail_cta_review_devices' => 'Geräte prüfen',
    'mail_outro_secure_account' => 'Wenn das nicht du warst, sichere bitte dein Benutzerkonto.',

    // Enforcement
    'device_blocked_title' => 'Geräte-Bestätigung erforderlich',
    'device_blocked_body' => 'Bitte bestätige dieses Gerät über den Link aus der E‑Mail, die wir dir gesendet haben.',

    // Devices
    'device_trusted' => 'Vertraut',
    'device_trust' => 'Vertrauen',
    'device_untrust' => 'Nicht vertrauen',

    'device_trust_modal_heading' => 'Dieses Gerät als vertraut markieren?',
    'device_trust_modal_description' => 'Der User kann dieses Gerät danach wieder normal verwenden.',
    'device_trust_success_title' => 'Gerät ist jetzt vertraut',

    'device_untrust_modal_heading' => 'Dieses Gerät auf „nicht vertrauen“ setzen?',
    'device_untrust_modal_description' => 'Der User wird auf diesem Gerät geblockt, bis er es erneut per E‑Mail bestätigt.',
    'device_untrust_success_title' => 'Gerät ist jetzt nicht vertraut',

    'device_delete' => 'Löschen',
    'device_delete_modal_heading' => 'Dieses Gerät löschen?',
    'device_delete_modal_description' => 'Das Gerät wird aus dem Benutzerkonto entfernt.',
    'device_delete_success_title' => 'Gerät gelöscht',
];
