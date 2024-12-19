<?php

return [
    'file' => 'bestand',
    'meta' => 'beschrijving',
    'author' => 'auteur',
    'image' => 'afbeelding',
    'images' => 'afbeeldingen',
    'information' => 'informatie',
    'edit-media' => 'bewerken',
    'edit-media-description' => 'sla extra informatie op voor dit mediabestand.',
    'move-media' => 'verplaats media naar',
    'move-media-description' => 'Momenteel in :name',
    'dimensions' => 'afmetingen',
    'description' => 'beschrijving',
    'type' => 'type',
    'caption' => 'onderschrift',
    'alt-text' => 'alt-tekst',
    'actions' => 'acties',
    'size' => 'grootte',
    'page' => 'pagina|pagina\'s',
    'duration' => 'duur',
    'root-folder' => 'root map',

    'time' => [
        'created_at' => 'aangemaakt op',
        'updated_at' => 'gewijzigd op',
        'published_at' => 'gepubliceerd op',
        'uploaded_at' => 'geüpload op',
        'uploaded_by' => 'geüpload door',
    ],

    'phrases' => [
        'select' => 'selecteer',
        'select-image' => 'selecteer afbeelding',
        'no' => 'nee',
        'found' => 'gevonden',
        'not-found' => 'niet gevonden',
        'upload' => 'uploaden',
        'upload-file' => 'bestand uploaden',
        'upload-image' => 'afbeelding uploaden',
        'replace-media' => 'media vervangen',
        'store' => 'opslaan',
        'store-images' => 'afbeelding opslaan|afbeeldingen opslaan',
        'details-for' => 'details voor',
        'view' => 'bekijk',
        'delete' => 'verwijder',
        'download' => 'download',
        'save' => 'opslaan',
        'edit' => 'bewerk',
        'from' => 'van',
        'to' => 'naar',
        'embed' => 'embed',
        'loading' => 'laden',
        'cancel' => 'annuleren',
        'update-and-close' => 'bijwerken en sluiten',
        'search' => 'zoeken',
        'confirm' => 'bevestigen',
        'create-folder' => 'maak map',
        'create' => 'maken',
        'rename-folder' => 'map hernoemen',
        'move-folder' => 'map verplaatsen',
        'move-media' => 'media verplaatsen',
        'delete-folder' => 'map verwijderen',
        'sort-by' => 'sorteer op',
        'regenerate' => 'regenereren',
        'requested' => 'aangevraagd',
        'select-all' => 'selecteer alle',
        'selected-item-suffix' => 'item geselecteerd',
        'selected-items-suffix-plural' => 'items geselecteerd',
    ],

    'warnings' => [
        'delete-media' => 'weet u zeker dat u :filename wilt verwijderen?',
    ],

    'sentences' => [
        'select-image-to-view-info' => 'selecteer een bestand om de informatie te bekijken.',
        'add-an-alt-text-to-this-image' => 'voeg een alt-tekst toe aan dit item.',
        'add-a-caption-to-this-image' => 'voeg een onderschrift/beschrijving toe aan dit item.',
        'enter-search-term' => 'voer een zoekterm in',
        'enter-folder-name' => 'voer een naam in voor de map',
        'folder-files' => '{0} Map is leeg|{1} 1 item|[2,*] :count items',
    ],

    'media' => [
        'choose-image' => 'kies afbeelding|kies afbeeldingen',
        'no-image-selected-yet' => 'nog geen item geselecteerd.',
        'storing-files' => 'bestanden opslaan...',
        'clear-image' => 'wissen',
        'warning-unstored-uploads' => 'Vergeet niet op \'opslaan\' te klikken om je bestand te uploaden|Vergeet niet op \'opslaan\' te klikken om je bestanden te uploaden',
        'will-be-available-soon' => 'Je media zal binnenkort beschikbaar zijn',

        'no-images-found' => [
            'title' => 'geen afbeeldingen gevonden',
            'description' => 'begin door je eerste item te uploaden.',
        ],
    ],

    'components' => [
        'browse-library' => [
            'breadcrumbs' => [
                'root' => 'Mediabibliotheek',
            ],
            'modals' => [
                'create-media-folder' => [
                    'heading' => 'Maak map',
                    'subheading' => 'De map wordt aangemaakt in de huidige map.',
                    'form' => [
                        'name' => [
                            'placeholder' => 'Mapnaam',
                        ],
                    ],
                    'messages' => [
                        'created' => [
                            'body' => 'Media map gemaakt',
                        ],
                    ],
                ],
                'rename-media-folder' => [
                    'heading' => 'Voer een nieuwe naam in voor deze map',
                    'form' => [
                        'name' => [
                            'placeholder' => 'Mapnaam',
                        ],
                    ],
                    'messages' => [
                        'renamed' => [
                            'body' => 'Media map hernoemd',
                        ],
                    ],
                ],
                'move-media-folder' => [
                    'heading' => 'Kies een nieuwe locatie voor deze map',
                    'subheading' => 'Alle items in de map worden ook verplaatst.',
                    'form' => [
                        'media_library_folder_id' => [
                            'placeholder' => 'Selecteer bestemming',
                        ],
                    ],
                    'messages' => [
                        'moved' => [
                            'body' => 'Media map verplaatst',
                        ],
                    ],
                ],
                'delete-media-folder' => [
                    'heading' => 'Weet u zeker dat u deze map wilt verwijderen?',
                    'subheading' => 'Eventuele bestanden in de map worden niet verwijderd, maar verplaatst naar de huidige map.',
                    'form' => [
                        'fields' => [
                            'include_children' => [
                                'label' => 'Verwijder alle inhoud',
                                'helper_text' => 'Waarschuwing: dit zal alle items uit de map verwijderen. Dit kan niet ongedaan gemaakt worden.',
                            ],
                        ],
                    ],
                    'messages' => [
                        'deleted' => [
                            'body' => 'Media map verwijderd',
                        ],
                    ],
                ],
            ],
            'sort_order' => [
                'created_at_ascending' => 'Oudste',
                'created_at_descending' => 'Nieuwste',
                'name_ascending' => 'Naam (A-Z)',
                'name_descending' => 'Naam (Z-A)',
            ],
        ],
        'media-info' => [
            'heading' => 'Bekijk media',
            'move-media-item-form' => [
                'fields' => [
                    'media_library_folder_id' => [
                        'placeholder' => 'Selecteer bestemming',
                    ],
                ],
                'messages' => [
                    'moved' => [
                        'body' => 'Media item verplaatst',
                    ],
                ],
            ],
        ],
        'media-picker' => [
            'title' => 'mediabibliotheek',
        ],
    ],

    'filament-tip-tap' => [
        'actions' => [
            'media-library-action' => [
                'modal-heading' => 'Kies media',
            ],
        ],
    ],
];
