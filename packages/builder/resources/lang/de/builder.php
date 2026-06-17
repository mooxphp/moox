<?php

return [
    'navigation_group' => 'Felder',

    'field_group' => [
        'single' => 'Feldgruppe',
        'plural' => 'Feldgruppen',
        'general' => 'Allgemein',
        'assignment' => 'Zuordnung',
        'fields' => 'Felder',
        'name' => 'Name',
        'name_helper' => 'Wird als Überschrift im Formular angezeigt.',
        'slug' => 'Technischer Schlüssel',
        'slug_helper' => 'Eindeutiger Bezeichner für diese Gruppe. Wird automatisch aus dem Namen erzeugt.',
        'active' => 'Aktiv',
        'active_helper' => 'Nur aktive Gruppen erscheinen in den Formularen.',
        'sort' => 'Reihenfolge',
        'sort_helper' => 'Niedrigere Werte werden weiter oben angezeigt.',
        'target_entities' => 'Anzeigen bei',
        'target_entities_helper' => 'Diese Feldgruppe erscheint in den Formularen der ausgewählten Ressourcen.',
        'target_entities_placeholder' => 'Ressource auswählen…',
        'no_entities_registered' => 'Noch keine Ressource mit HasCustomFields. Trait an einer Filament-Resource hinzufügen.',
        'field_item' => 'Feld',
        'fields_count' => 'Felder',
        'assigned_to' => 'Zugeordnet zu',
    ],

    'field' => [
        'label' => 'Bezeichnung',
        'label_helper' => 'Sichtbarer Name im Formular.',
        'name' => 'Feldschlüssel',
        'name_helper' => 'Technischer Name für Speicherung und Abfragen. Nur Kleinbuchstaben, Zahlen und Bindestriche.',
        'type' => 'Feldtyp',
        'required' => 'Pflichtfeld',
        'required_badge' => 'Pflicht',
        'subfields_count' => '{1} :count Unterfeld|[2,*] :count Unterfelder',
        'settings' => 'Einstellungen',
        'options' => 'Auswahloptionen',
        'option_label' => 'Anzeigetext',
        'option_value' => 'Wert',
        'subfields' => 'Unterfelder',
    ],

    'field_types' => [
        'text' => 'Text (kurz)',
        'textarea' => 'Text (mehrzeilig)',
        'number' => 'Zahl',
        'range' => 'Bereich',
        'email' => 'E-Mail',
        'url' => 'URL',
        'password' => 'Passwort',
        'select' => 'Auswahl (Dropdown)',
        'multiselect' => 'Mehrfachauswahl',
        'checkbox_list' => 'Checkbox-Liste',
        'radio' => 'Optionsfelder',
        'button_group' => 'Button-Gruppe',
        'toggle' => 'Schalter',
        'date' => 'Datum',
        'datetime' => 'Datum & Uhrzeit',
        'time' => 'Uhrzeit',
        'color' => 'Farbe',
        'link' => 'Link',
        'rich_text' => 'Rich Text',
        'message' => 'Hinweis',
        'oembed' => 'oEmbed',
        'tab' => 'Tab',
        'group' => 'Gruppe',
        'repeater' => 'Repeater',
    ],

    'message' => [
        'body' => 'Hinweistext',
    ],

    'oembed' => [
        'helper' => 'Video- oder Embed-URL einfügen (YouTube, Vimeo, …).',
    ],

    'link' => [
        'url' => 'URL',
        'label' => 'Bezeichnung',
        'opens_in_new_tab' => 'In neuem Tab öffnen',
    ],

    'capabilities' => [
        'max_length' => 'Maximale Länge',
        'placeholder' => 'Platzhalter',
        'prefix' => 'Präfix',
        'suffix' => 'Suffix',
        'default_value' => 'Standardwert',
        'helper_text' => 'Hilfetext',
        'min_value' => 'Mindestwert',
        'max_value' => 'Höchstwert',
        'min_items' => 'Mindestanzahl Einträge',
        'max_items' => 'Höchstanzahl Einträge',
        'step' => 'Schrittweite',
        'rows' => 'Zeilen',
        'display_format' => 'Anzeigeformat',
    ],

    'repeater' => [
        'collapse_all' => 'Alle einklappen',
        'expand_all' => 'Alle ausklappen',
    ],

    'validation' => [
        'invalid_option' => 'Der gewählte Wert ist keine gültige Option.',
        'duplicate_field_name' => 'Der Feldschlüssel „:name“ wird bereits in der Gruppe „:group“ verwendet.',
        'duplicate_field_name_internal' => 'Der Feldschlüssel „:name“ ist in dieser Gruppe mehrfach vergeben.',
    ],
];
