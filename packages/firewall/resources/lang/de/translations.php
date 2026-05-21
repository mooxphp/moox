<?php

return [
    'message' => 'Moox Firewall',
    'description' => 'Bitte gib dein Zugangstoken ein, um fortzufahren.',
    'denied_message' => 'Zugriff verweigert. Bitte kontaktiere die IT-Abteilung.',

    'backdoor_title' => 'Moox Firewall',
    'backdoor_continue' => 'Weiter',
    'backdoor_placeholder' => 'Zugangstoken eingeben',

    'error_invalid_token' => 'Ungültiges Token. Bitte versuche es erneut.',
    'error_too_many_attempts' => 'Zu viele Versuche. Bitte warte eine Minute und versuche es erneut.',

    'resource' => [
        'navigation_label' => 'Firewall-Whitelist',
        'navigation_group' => 'Sicherheit',

        'ip_address' => 'IP-Adresse',
        'label' => 'Name',
        'active' => 'Aktiv',
        'allow_all_routes' => 'Alle geschützten Routen erlauben',
        'all_routes' => 'Alle Routen',
        'allowed_routes' => 'Erlaubte Routen (Patterns)',
        'allowed_routes_hint' => 'Wähle ein Wildcard-Pattern wie "admin/*" aus der Liste, oder tippe um einzelne Routen zu suchen (z.B. "admin/users"). Patterns müssen zu `Request::is` passen.',
        'allowed_routes_ignored' => 'Erlaubte Routen werden ignoriert, da "Alle geschützten Routen erlauben" aktiv ist.',
        'updated' => 'Aktualisiert',
    ],
];
