<?php

return [
    'file' => 'Datei',
    'meta' => 'Beschreibung',
    'author' => 'Autor',
    'image' => 'Medium',
    'images' => 'Medien',
    'information' => 'Informationen',
    'edit-media' => 'Medien bearbeiten',
    'edit-media-description' => 'zusätzliche Informationen zu diesem Medienelement speichern.',
    'move-media' => 'Medien verschieben nach',
    'move-media-description' => 'Derzeit in :name',
    'dimensions' => 'Abmessungen',
    'description' => 'Beschreibung',
    'type' => 'Typ',
    'caption' => 'Bildunterschrift',
    'alt-text' => 'Alt-Text',
    'actions' => 'Aktionen',
    'size' => 'Größe',
    'page' => 'Seite|Seiten',
    'duration' => 'Dauer',
    'root-folder' => 'Stammverzeichnis',

    'time' => [
        'created_at' => 'erstellt am',
        'updated_at' => 'geändert am',
        'published_at' => 'veröffentlicht am',
        'uploaded_at' => 'hochgeladen am',
        'uploaded_by' => 'hochgeladen von',
    ],

    'phrases' => [
        'select' => 'auswählen',
        'select-image' => 'Medium auswählen',
        'no' => 'nein',
        'found' => 'gefunden',
        'not-found' => 'nicht gefunden',
        'upload' => 'hochladen',
        'upload-file' => 'Datei hochladen',
        'upload-image' => 'Medium hochladen',
        'replace-media' => 'Medium ersetzen',
        'store' => 'speichern',
        'store-images' => 'Medium speichern|Medien speichern',
        'details-for' => 'Details für',
        'view' => 'anschauen',
        'delete' => 'löschen',
        'download' => 'herunterladen',
        'save' => 'speichern',
        'edit' => 'Bearbeiten',
        'from' => 'von',
        'to' => 'bis',
        'embed' => 'einbetten',
        'loading' => 'lädt',
        'cancel' => 'abbrechen',
        'update-and-close' => 'aktualisieren und schließen',
        'search' => 'suchen',
        'confirm' => 'bestätigen',
        'create-folder' => 'Ordner erstellen',
        'create' => 'erstellen',
        'rename-folder' => 'Ordner umbenennen',
        'move-folder' => 'Ordner verschieben',
        'move-media' => 'Medium verschieben',
        'delete-folder' => 'Ordner löschen',
        'sort-by' => 'Sortieren nach',
        'regenerate' => 'Regenerieren',
        'requested' => 'Angefordert',
    ],

    'warnings' => [
        'delete-media' => 'Sind Sie sicher, dass Sie :filename löschen möchten?',
    ],

    'sentences' => [
        'select-image-to-view-info' => 'Wählen Sie eine Datei aus, um ihre Informationen anzusehen.',
        'add-an-alt-text-to-this-image' => 'Fügen Sie diesem Element einen Alt-Text hinzu.',
        'add-a-caption-to-this-image' => 'Fügen Sie diesem Element eine Bildunterschrift/Beschreibung hinzu.',
        'enter-search-term' => 'Geben Sie einen Suchbegriff ein',
        'enter-folder-name' => 'Geben Sie einen Namen für den Ordner ein',
        'folder-files' => '{0} Ordner ist leer|{1} 1 Datei|[2,*] :count Dateien',
    ],
    'media' => [
        'choose-image' => 'Medium auswählen|Medien auswählen',
        'no-image-selected-yet' => '',
        'storing-files' => 'Dateien speichern...',
        'clear-image' => 'löschen',
        'warning-unstored-uploads' => 'Vergessen Sie nicht, auf \'speichern\' zu klicken, um Ihre Datei hochzuladen|Vergessen Sie nicht, auf \'speichern\' zu klicken, um Ihre Dateien hochzuladen',
        'will-be-available-soon' => 'Ihre Medien werden bald verfügbar sein',

        'no-images-found' => [
            'title' => 'Keine Medien gefunden',
            'description' => 'Beginnen Sie, indem Sie Ihr erstes Element hochladen.',
        ],
    ],

    'components' => [
        'browse-library' => [
            'breadcrumbs' => [
                'root' => 'Medienbibliothek',
            ],
            'modals' => [
                'create-media-folder' => [
                    'heading' => 'Ordner erstellen',
                    'subheading' => 'Der Ordner wird im aktuellen Ordner erstellt.',
                    'form' => [
                        'name' => [
                            'placeholder' => 'Ordnername',
                        ],
                    ],
                    'messages' => [
                        'created' => [
                            'body' => 'Medienordner erstellt',
                        ],
                    ],
                ],
                'rename-media-folder' => [
                    'heading' => 'Geben Sie diesem Ordner einen neuen Namen',
                    'form' => [
                        'name' => [
                            'placeholder' => 'Ordnername',
                        ],
                    ],
                    'messages' => [
                        'renamed' => [
                            'body' => 'Medienordner umbenannt',
                        ],
                    ],
                ],
                'move-media-folder' => [
                    'heading' => 'Wählen Sie einen neuen Standort für diesen Ordner',
                    'subheading' => 'Alle Elemente im Ordner werden ebenfalls verschoben.',
                    'form' => [
                        'media_library_folder_id' => [
                            'placeholder' => 'Ziel auswählen',
                        ],
                    ],
                    'messages' => [
                        'moved' => [
                            'body' => 'Medienordner verschoben',
                        ],
                    ],
                ],
                'delete-media-folder' => [
                    'heading' => 'Sind Sie sicher, dass Sie diesen Ordner löschen möchten?',
                    'subheading' => 'Dateien im Ordner werden nicht gelöscht, sondern in den aktuellen Ordner verschoben.',
                    'form' => [
                        'fields' => [
                            'include_children' => [
                                'label' => 'Alle Inhalte im Ordner löschen',
                                'helper_text' => 'Warnung: Dadurch werden alle Elemente im Ordner gelöscht. Dies kann nicht rückgängig gemacht werden.',
                            ],
                        ],
                    ],
                    'messages' => [
                        'deleted' => [
                            'body' => 'Medienordner gelöscht',
                        ],
                    ],
                ],
            ],
            'sort_order' => [
                'created_at_ascending' => 'Älteste',
                'created_at_descending' => 'Neueste',
                'name_ascending' => 'Name (A-Z)',
                'name_descending' => 'Name (Z-A)',
            ],
        ],
        'media-info' => [
            'move-media-item-form' => [
                'fields' => [
                    'media_library_folder_id' => [
                        'placeholder' => 'Ziel auswählen',
                    ],
                ],
                'messages' => [
                    'moved' => [
                        'body' => 'Medienelement verschoben',
                    ],
                ],
            ],
        ],
        'media-picker' => [
            'title' => 'Medienbibliothek',
        ],
    ],

    'filament-tip-tap' => [
        'actions' => [
            'media-library-action' => [
                'modal-heading' => 'Medien auswählen',
            ],
        ],
    ],
];
