<?php

return [
    'file' => 'file',
    'meta' => 'description',
    'author' => 'author',
    'image' => 'image',
    'images' => 'images',
    'information' => 'information',
    'edit-media' => 'edit',
    'edit-media-description' => 'save additional information to this media item.',
    'move-media' => 'move media to',
    'move-media-description' => 'Currently in :name',
    'dimensions' => 'dimensions',
    'description' => 'description',
    'type' => 'type',
    'caption' => 'caption',
    'alt-text' => 'alt-text',
    'actions' => 'actions',
    'size' => 'size',
    'page' => 'page|pages',
    'duration' => 'duration',
    'root-folder' => 'root folder',

    'time' => [
        'created_at' => 'created at',
        'updated_at' => 'modified at',
        'published_at' => 'published at',
        'uploaded_at' => 'uploaded at',
        'uploaded_by' => 'uploaded by',
    ],

    'phrases' => [
        'select' => 'select',
        'select-image' => 'select image',
        'no' => 'no',
        'found' => 'found',
        'not-found' => 'not found',
        'upload' => 'upload',
        'upload-file' => 'upload file',
        'upload-image' => 'upload image',
        'replace-media' => 'replace media',
        'store' => 'store',
        'store-images' => 'store image|store images',
        'details-for' => 'details for',
        'view' => 'view',
        'delete' => 'delete',
        'download' => 'download',
        'save' => 'save',
        'edit' => 'Edit',
        'from' => 'from',
        'to' => 'to',
        'embed' => 'embed',
        'loading' => 'loading',
        'cancel' => 'cancel',
        'update-and-close' => 'update and close',
        'search' => 'search',
        'confirm' => 'confirm',
        'create-folder' => 'create folder',
        'create' => 'create',
        'rename-folder' => 'rename folder',
        'move-folder' => 'move folder',
        'move-media' => 'move media',
        'delete-folder' => 'delete folder',
        'sort-by' => 'sort by',
        'regenerate' => 'regenerate',
        'requested' => 'requested',
        'select-all' => 'select all',
        'selected-item-suffix' => 'item selected',
        'selected-items-suffix-plural' => 'items selected',
    ],

    'warnings' => [
        'delete-media' => 'are you sure you want to delete :filename?',
    ],

    'sentences' => [
        'select-image-to-view-info' => 'select a file to view its information.',
        'add-an-alt-text-to-this-image' => 'add alt-text to this item.',
        'add-a-caption-to-this-image' => 'add a caption/description to this item.',
        'enter-search-term' => 'enter a term to search',
        'enter-folder-name' => 'enter a term for the folder',
        'folder-files' => '{0} Folder is empty|{1} 1 item|[2,*] :count items',
    ],

    'media' => [
        'choose-image' => 'choose image|choose images',
        'no-image-selected-yet' => 'no item selected yet.',
        'storing-files' => 'storing files...',
        'clear-image' => 'clear',
        'warning-unstored-uploads' => 'Don\'t forget to click \'store\' to upload your file|Don\'t forget to click \'store\' to upload your files',
        'will-be-available-soon' => 'Your media will be available soon',

        'no-images-found' => [
            'title' => 'no images found',
            'description' => 'get started by uploading your first item.',
        ],
    ],

    'components' => [
        'browse-library' => [
            'breadcrumbs' => [
                'root' => 'Media library',
            ],
            'modals' => [
                'create-media-folder' => [
                    'heading' => 'Create folder',
                    'subheading' => 'The folder will be created in the current folder.',
                    'form' => [
                        'name' => [
                            'placeholder' => 'Folder name',
                        ],
                    ],
                    'messages' => [
                        'created' => [
                            'body' => 'Media folder created',
                        ],
                    ],
                ],
                'rename-media-folder' => [
                    'heading' => 'Enter a new name for this folder',
                    'form' => [
                        'name' => [
                            'placeholder' => 'Folder name',
                        ],
                    ],
                    'messages' => [
                        'renamed' => [
                            'body' => 'Media folder renamed',
                        ],
                    ],
                ],
                'move-media-folder' => [
                    'heading' => 'Choose a new location for this folder',
                    'subheading' => 'All items inside the folder will be moved as well.',
                    'form' => [
                        'media_library_folder_id' => [
                            'placeholder' => 'Select destination',
                        ],
                    ],
                    'messages' => [
                        'moved' => [
                            'body' => 'Media folder moved',
                        ],
                    ],
                ],
                'delete-media-folder' => [
                    'heading' => 'Are you sure you want to delete this folder?',
                    'subheading' => 'Any files in the folder will not be deleted, but moved to the current folder.',
                    'form' => [
                        'fields' => [
                            'include_children' => [
                                'label' => 'Delete all content in folder',
                                'helper_text' => 'Warning: this will delete all items in the folder. This cannot be undone.',
                            ],
                        ],
                    ],
                    'messages' => [
                        'deleted' => [
                            'body' => 'Media folder deleted',
                        ],
                    ],
                ],
            ],
            'sort_order' => [
                'created_at_ascending' => 'Oldest',
                'created_at_descending' => 'Newest',
                'name_ascending' => 'Name (A-Z)',
                'name_descending' => 'Name (Z-A)',
            ],
        ],
        'media-info' => [
            'heading' => 'View item',
            'move-media-item-form' => [
                'fields' => [
                    'media_library_folder_id' => [
                        'placeholder' => 'Select destination',
                    ],
                ],
                'messages' => [
                    'moved' => [
                        'body' => 'Media item moved',
                    ],
                ],
            ],
        ],
        'media-picker' => [
            'title' => 'media library',
        ],
    ],

    'filament-tip-tap' => [
        'actions' => [
            'media-library-action' => [
                'modal-heading' => 'Choose media',
                'modal-submit-action-label' => 'Select',
            ],
        ],
    ],
];
