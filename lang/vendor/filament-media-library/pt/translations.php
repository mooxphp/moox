<?php

return [
    'file' => 'arquivo',
    'meta' => 'descrição',
    'author' => 'autor',
    'image' => 'imagem',
    'images' => 'imagens',
    'information' => 'informação',
    'edit-media' => 'editar',
    'edit-media-description' => 'adicionar informações a este arquivo.',
    'move-media' => 'mover arquivo para',
    'move-media-description' => 'Atualmente em :name',
    'dimensions' => 'dimensões',
    'description' => 'descrição',
    'type' => 'tipo',
    'caption' => 'legenda',
    'alt-text' => 'descrição',
    'actions' => 'ações',
    'size' => 'tamanho',
    'page' => 'página|páginas',
    'duration' => 'duração',
    'root-folder' => 'pasta raiz',

    'time' => [
        'created_at' => 'criado em',
        'updated_at' => 'modificado em',
        'published_at' => 'publicado em',
        'uploaded_at' => 'enviado em',
        'uploaded_by' => 'enviado por',
    ],

    'phrases' => [
        'select' => 'selecionar',
        'select-image' => 'selecionar arquivo',
        'no' => 'não',
        'found' => 'encontrado',
        'not-found' => 'não encontrado',
        'upload' => 'enviar',
        'upload-file' => 'enviar arquivo',
        'upload-image' => 'enviar imagem',
        'replace-media' => 'substituir arquivo',
        'store' => 'salvar',
        'store-images' => 'salvar imagem|salvar imagens',
        'details-for' => 'detalhes para',
        'view' => 'ver',
        'delete' => 'remover',
        'download' => 'baixar',
        'save' => 'salvar',
        'edit' => 'Editar',
        'from' => 'de',
        'to' => 'para',
        'embed' => 'embed',
        'loading' => 'carregando',
        'cancel' => 'cancelar',
        'update-and-close' => 'atualizar e fechar',
        'search' => 'buscar',
        'confirm' => 'confirmar',
        'create-folder' => 'criar pasta',
        'create' => 'criar',
        'rename-folder' => 'renomear pasta',
        'move-folder' => 'mover pasta',
        'move-media' => 'mover arquivo',
        'delete-folder' => 'remover pasta',
        'sort-by' => 'Ordenar por',
        'regenerate' => 'Re-gerar',
        'requested' => 'Requisitado',
    ],

    'warnings' => [
        'delete-media' => 'tem certeza que deseja remover o arquivo :filename?',
    ],

    'sentences' => [
        'select-image-to-view-info' => 'Selecione um arquivo para ver os detalhes.',
        'add-an-alt-text-to-this-image' => 'Adicionar descrição para este arquivo.',
        'add-a-caption-to-this-image' => 'Adicionar legenda para este arquivo.',
        'enter-search-term' => 'digite para pesquisar',
        'enter-folder-name' => 'enter a term for the folder',
        'folder-files' => '{0} A pasta está vazia|{1} 1 arquivo|[2,*] :count arquivos',
    ],

    'media' => [
        'choose-image' => 'escolher imagem|escolher imagens',
        'no-image-selected-yet' => 'nenhum arquivo selecionado',
        'storing-files' => 'armazenando arquivos...',
        'clear-image' => 'limpar',
        'warning-unstored-uploads' => 'Não esqueça de clicar em \'salvar\' para enviar seu arquivo|Não esqueça de clicar em \'salvar\' para enviar seus arquivos',
        'will-be-available-soon' => 'Seu arquivo estará disponível em breve',

        'no-images-found' => [
            'title' => 'Nenhuma imagem encontrada',
            'description' => 'comece enviando seu primeiro arquivo.',
        ],
    ],

    'components' => [
        'browse-library' => [
            'breadcrumbs' => [
                'root' => 'Arquivos',
            ],
            'modals' => [
                'create-media-folder' => [
                    'heading' => 'Criar pasta',
                    'subheading' => 'A pasta será criada dentro da pasta atual.',
                    'form' => [
                        'name' => [
                            'placeholder' => 'Nome da pasta',
                        ],
                    ],
                    'messages' => [
                        'created' => [
                            'body' => 'Pasta criada',
                        ],
                    ],
                ],
                'rename-media-folder' => [
                    'heading' => 'Digite um novo nome para esta pasta',
                    'form' => [
                        'name' => [
                            'placeholder' => 'Nome da pasta',
                        ],
                    ],
                    'messages' => [
                        'renamed' => [
                            'body' => 'Pasta renomeada',
                        ],
                    ],
                ],
                'move-media-folder' => [
                    'heading' => 'Escolha uma pasta de destino',
                    'subheading' => 'Todos os arquivos dentro desta pasta também serão movidos para a pasta selecionada.',
                    'form' => [
                        'media_library_folder_id' => [
                            'placeholder' => 'Selecione um destino',
                        ],
                    ],
                    'messages' => [
                        'moved' => [
                            'body' => 'Pasta movida',
                        ],
                    ],
                ],
                'delete-media-folder' => [
                    'heading' => 'Tem certeza que deseja remover esta pasta?',
                    'subheading' => 'Qualquer arquivo dentro desta pasta não será removido. Os arquivos serão movidos para a pasta atual.',
                    'messages' => [
                        'deleted' => [
                            'body' => 'Pasta removida',
                        ],
                    ],
                ],
            ],
        ],
        'media-info' => [
            'move-media-item-form' => [
                'fields' => [
                    'media_library_folder_id' => [
                        'placeholder' => 'Selecione um destino',
                    ],
                ],
                'messages' => [
                    'moved' => [
                        'body' => 'Arquivo movido',
                    ],
                ],
            ],
        ],
        'media-picker' => [
            'title' => 'arquivos',
        ],
    ],
];
