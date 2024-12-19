<?php

return [
    'file' => 'file',
    'meta' => 'descrizione',
    'author' => 'autore',
    'image' => 'immagine',
    'images' => 'immagini',
    'information' => 'informazioni',
    'edit-media' => 'modifica',
    'edit-media-description' => 'salva informazioni aggiuntive per questo elemento multimediale.',
    'move-media' => 'sposta media in',
    'move-media-description' => 'Attualmente in :name',
    'dimensions' => 'dimensioni',
    'description' => 'descrizione',
    'type' => 'tipo',
    'caption' => 'didascalia',
    'alt-text' => 'testo alternativo',
    'actions' => 'azioni',
    'size' => 'dimensione',
    'page' => 'pagina|pagine',
    'duration' => 'durata',
    'root-folder' => 'cartella principale',

    'time' => [
        'created_at' => 'creato il',
        'updated_at' => 'modificato il',
        'published_at' => 'pubblicato il',
        'uploaded_at' => 'caricato il',
        'uploaded_by' => 'caricato da',
    ],

    'phrases' => [
        'select' => 'seleziona',
        'select-image' => 'seleziona immagine',
        'no' => 'no',
        'found' => 'trovato',
        'not-found' => 'non trovato',
        'upload' => 'carica',
        'upload-file' => 'carica file',
        'upload-image' => 'carica immagine',
        'replace-media' => 'sostituisci media',
        'store' => 'salva',
        'store-images' => 'salva immagine|salva immagini',
        'details-for' => 'dettagli per',
        'view' => 'vedi',
        'delete' => 'elimina',
        'download' => 'scarica',
        'save' => 'salva',
        'edit' => 'modifica',

        'from' => 'da',
        'to' => 'a',
        'embed' => 'embed',
        'loading' => 'caricamento',
        'cancel' => 'annulla',
        'update-and-close' => 'aggiorna e chiudi',
        'search' => 'cerca',
        'confirm' => 'conferma',
        'create-folder' => 'crea cartella',
        'create' => 'crea',
        'rename-folder' => 'rinomina cartella',
        'move-folder' => 'sposta cartella',
        'move-media' => 'sposta media',
        'delete-folder' => 'elimina cartella',
        'sort-by' => 'Ordina per',
        'regenerate' => 'Rigenera',
        'requested' => 'Richiesto',
    ],

    'warnings' => [
        'delete-media' => 'Sei sicuro di voler eliminare :filename?',
    ],

    'sentences' => [
        'select-image-to-view-info' => 'seleziona un file per visualizzarne le informazioni.',
        'add-an-alt-text-to-this-image' => 'aggiungi un testo alternativo a questo elemento.',
        'add-a-caption-to-this-image' => 'aggiungi una didascalia/descrizione a questo elemento.',
        'enter-search-term' => 'termine ricerca',
        'enter-folder-name' => 'inserisci un termine per la cartella',
        'folder-files' => '{0} La cartella è vuota|{1} 1 file|[2,*] :count files',
    ],

    'media' => [
        'choose-image' => 'scegli un\'immagine|scegli immagini',
        'no-image-selected-yet' => 'nessun elemento selezionato ancora.',
        'storing-files' => 'archiviazione dei file in corso...',
        'clear-image' => 'Svuota',
        'warning-unstored-uploads' => '\'Non dimenticare di fare clic su \'archivia\' per caricare il tuo file|Non dimenticare di fare clic su \'archivia\' per caricare i tuoi file',
        'will-be-available-soon' => 'I tuoi media saranno presto disponibili',

        'no-images-found' => [
            'title' => 'nessuna immagine trovata',
            'description' => 'inizia caricando il tuo primo elemento.',
        ],
    ],

    'components' => [
        'browse-library' => [
            'breadcrumbs' => [
                'root' => 'Libreria media',
            ],
            'modals' => [
                'create-media-folder' => [
                    'heading' => 'Crea cartella',
                    'subheading' => 'La cartella verrà creata nella cartella corrente.',
                    'form' => [
                        'name' => [
                            'placeholder' => 'Nome della cartella',
                        ],
                    ],
                    'messages' => [
                        'created' => [
                            'body' => 'Cartella multimediale creata',
                        ],
                    ],
                ],
                'rename-media-folder' => [
                    'heading' => 'Inserisci un nuovo nome per questa cartella',
                    'form' => [
                        'name' => [
                            'placeholder' => 'Nome della cartella',
                        ],
                    ],
                    'messages' => [
                        'renamed' => [
                            'body' => 'Cartella multimediale rinominata',
                        ],
                    ],
                ],
                'move-media-folder' => [
                    'heading' => 'Scegli una nuova posizione per questa cartella',
                    'subheading' => "Tutti gli elementi all'interno della cartella verranno spostati.",
                    'form' => [
                        'media_library_folder_id' => [
                            'placeholder' => 'Seleziona destinazione',
                        ],
                    ],
                    'messages' => [
                        'moved' => [
                            'body' => 'Cartella multimediale spostata',
                        ],
                    ],
                ],
                'delete-media-folder' => [
                    'heading' => 'Sei sicuro di voler eliminare questa cartella?',
                    'subheading' => "Tutti gli elementi all'interno della cartella verranno eliminati. Questa azione non può essere annullata.",
                    'confirm-button-text' => 'Elimina',
                    'cancel-button-text' => 'Annulla',
                ],
            ],
        ],

        'media-info' => [
            'move-media-item-form' => [
                'fields' => [
                    'media_library_folder_id' => [
                        'placeholder' => 'Seleziona destinazione',
                    ],
                ],
                'messages' => [
                    'moved' => [
                        'body' => 'Elemento multimediale spostato',
                    ],
                ],
            ],
        ],
        'media-picker' => [
            'title' => 'Libreria media',
        ],
    ],
];
