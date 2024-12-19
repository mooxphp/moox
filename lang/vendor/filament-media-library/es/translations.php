<?php

return [
    'file' => 'archivo',
    'meta' => 'descripción',
    'author' => 'autor',
    'image' => 'imagen',
    'images' => 'imágenes',
    'information' => 'información',
    'edit-media' => 'editar',
    'edit-media-description' => 'guardar información adicional para este elemento de medios.',
    'move-media' => 'mover medios a',
    'move-media-description' => 'Actualmente en :name',
    'dimensions' => 'dimensiones',
    'description' => 'descripción',
    'type' => 'tipo',
    'caption' => 'subtítulo',
    'alt-text' => 'texto alternativo',
    'actions' => 'acciones',
    'size' => 'tamaño',
    'page' => 'página|páginas',
    'duration' => 'duración',
    'root-folder' => 'carpeta raíz',

    'time' => [
        'created_at' => 'creado el',
        'updated_at' => 'modificado el',
        'published_at' => 'publicado el',
        'uploaded_at' => 'subido el',
        'uploaded_by' => 'subido por',
    ],

    'phrases' => [
        'select' => 'seleccionar',
        'select-image' => 'seleccionar imagen',
        'no' => 'no',
        'found' => 'encontrado',
        'not-found' => 'no encontrado',
        'upload' => 'subir',
        'upload-file' => 'subir archivo',
        'upload-image' => 'subir imagen',
        'replace-media' => 'reemplazar medios',
        'store' => 'almacenar',
        'store-images' => 'almacenar imagen|almacenar imágenes',
        'details-for' => 'detalles para',
        'view' => 'ver',
        'delete' => 'eliminar',
        'download' => 'descargar',
        'save' => 'guardar',
        'edit' => 'editar',
        'from' => 'desde',
        'to' => 'a',
        'embed' => 'incrustar',
        'loading' => 'cargando',
        'cancel' => 'cancelar',
        'update-and-close' => 'actualizar y cerrar',
        'search' => 'buscar',
        'confirm' => 'confirmar',
        'create-folder' => 'crear carpeta',
        'create' => 'crear',
        'rename-folder' => 'renombrar carpeta',
        'move-folder' => 'mover carpeta',
        'move-media' => 'mover medios',
        'delete-folder' => 'eliminar carpeta',
        'sort-by' => 'ordenar por',
        'regenerate' => 'regenerar',
        'requested' => 'solicitado',
        'select-all' => 'seleccionar todo',
        'selected-item-suffix' => 'elemento seleccionado',
        'selected-items-suffix-plural' => 'elementos seleccionados',
    ],

    'warnings' => [
        'delete-media' => '¿Está seguro de que desea eliminar :filename?',
    ],

    'sentences' => [
        'select-image-to-view-info' => 'selecciona una imagen para ver su información.',
        'add-an-alt-text-to-this-image' => 'añadir un texto alternativo a esta imagen.',
        'add-a-caption-to-this-image' => 'añadir una subtítulo/descripción a esta imagen.',
        'enter-search-term' => 'ingrese un término para buscar',
        'enter-folder-name' => 'ingrese un término para la carpeta',
        'folder-files' => '{0} Carpeta vacía|{1} 1 archivo|[2,*] :count archivos',
    ],

    'media' => [
        'choose-image' => 'seleccionar imagen|seleccionar imágenes',
        'no-image-selected-yet' => 'no hay elemento seleccionado.',
        'storing-files' => 'almacenando archivos...',
        'clear-image' => 'borrar',
        'warning-unstored-uploads' => '¡No olvide hacer clic en \'almacenar imagen\' para subir su archivo!',
        'will-be-available-soon' => 'Su archivo estará disponible pronto',

        'no-images-found' => [
            'title' => 'no se han encontrado imágenes',
            'description' => '¡Empieza subiendo tu primer elemento!',
        ],
    ],

    'components' => [
        'browse-library' => [
            'breadcrumbs' => [
                'root' => 'Biblioteca de medios',
            ],
            'modals' => [
                'create-media-folder' => [
                    'heading' => 'Crear carpeta',
                    'subheading' => 'La carpeta se creará en la carpeta actual.',
                    'form' => [
                        'name' => [
                            'placeholder' => 'Nombre de la carpeta',
                        ],
                    ],
                    'messages' => [
                        'created' => [
                            'body' => 'Carpeta de medios creada',
                        ],
                    ],
                ],
                'rename-media-folder' => [
                    'heading' => 'Ingrese un nuevo nombre para esta carpeta',
                    'form' => [
                        'name' => [
                            'placeholder' => 'Nombre de la carpeta',
                        ],
                    ],
                    'messages' => [
                        'renamed' => [
                            'body' => 'Carpeta de medios renombrada',
                        ],
                    ],
                ],
                'move-media-folder' => [
                    'heading' => 'Elija una nueva ubicación para esta carpeta',
                    'subheading' => 'Todos los elementos dentro de la carpeta se moverán también.',
                    'form' => [
                        'media_library_folder_id' => [
                            'placeholder' => 'Seleccione un destino',
                        ],
                    ],
                    'messages' => [
                        'moved' => [
                            'body' => 'Carpeta de medios movida',
                        ],
                    ],
                ],
                'delete-media-folder' => [
                    'heading' => '¿Está seguro de que desea eliminar esta carpeta?',
                    'subheading' => 'Cualquier elemento en la carpeta no se eliminará, pero se moverá a la carpeta actual.',
                    'form' => [
                        'fields' => [
                            'include_children' => [
                                'label' => 'Eliminar todos los elementos de la carpeta',
                                'helper_text' => 'Advertencia: esto no eliminará los elementos de la carpeta. Esto no se puede deshacer.',
                            ],
                        ],
                    ],
                    'messages' => [
                        'deleted' => [
                            'body' => 'Carpeta de medios eliminada',
                        ],
                    ],
                ],
            ],
            'sort_order' => [
                'created_at_ascending' => 'Antiguo',
                'created_at_descending' => 'Nuevo',
                'name_ascending' => 'Nombre (A-Z)',
                'name_descending' => 'Nombre (Z-A)',
            ],
        ],
        'media-info' => [
            'heading' => 'Ver elemento',
            'move-media-item-form' => [
                'fields' => [
                    'media_library_folder_id' => [
                        'placeholder' => 'Seleccione un destino',
                    ],
                ],
                'messages' => [
                    'moved' => [
                        'body' => 'Elemento de medios movido',
                    ],
                ],
            ],
        ],
        'media-picker' => [
            'title' => 'biblioteca de medios',
        ],
    ],

    'filament-tip-tap' => [
        'actions' => [
            'media-library-action' => [
                'modal-heading' => 'Elegir medio',
            ],
        ],
    ],
];
