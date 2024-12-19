<?php

return [
    'file' => 'файл',
    'meta' => 'описание',
    'author' => 'автор',
    'image' => 'image',
    'images' => 'images',
    'information' => 'информация',
    'edit-media' => 'редактиране',
    'edit-media-description' => 'запазване на допълнителна информация към този медиен елемент.',
    'move-media' => 'преместване на медия в',
    'move-media-description' => 'В момента в :name',
    'dimensions' => 'размери',
    'description' => 'описание',
    'type' => 'вид',
    'caption' => 'надпис',
    'alt-text' => 'алтернативен текст',
    'actions' => 'действия',
    'size' => 'размер',
    'page' => 'траница|страници',
    'duration' => 'продължителност',
    'root-folder' => 'главна папка',

    'time' => [
        'created_at' => 'създадено в',
        'updated_at' => 'модифициран на',
        'published_at' => 'публикуван в',
        'uploaded_at' => 'качено в',
        'uploaded_by' => 'качено от',
    ],

    'phrases' => [
        'select' => 'изберете',
        'select-image' => 'изберете изображение',
        'no' => 'не',
        'found' => 'намерено',
        'not-found' => 'не е намерено',
        'upload' => 'качване',
        'upload-file' => 'качване на файл',
        'upload-image' => 'качване на изображение',
        'replace-media' => 'замяна на медия',
        'store' => 'хранилище',
        'store-images' => 'съхраняване на изображение|съхраняване на изображения',
        'details-for' => 'подробности за',
        'view' => 'преглед',
        'delete' => 'изтрий',
        'download' => 'свали',
        'save' => 'запис',
        'edit' => 'Редакция',
        'from' => 'от',
        'to' => 'до',
        'embed' => 'вграждане',
        'loading' => 'зареждане',
        'cancel' => 'прекрати',
        'update-and-close' => 'актуализиране и затваряне',
        'search' => 'търсене',
        'confirm' => 'потвърди',
        'create-folder' => 'създаване на папка',
        'create' => 'създай',
        'rename-folder' => 'преименуване на папка',
        'move-folder' => 'преместване на папка',
        'move-media' => 'преместване на медии',
        'delete-folder' => 'изтриване на папка',
        'sort-by' => 'Сортиране по',
        'regenerate' => 'Регенериране',
        'requested' => 'Поискано',
        'select-all' => 'Избери всички',
        'selected-item-suffix' => 'избран артикул',
        'selected-items-suffix-plural' => 'избрани елементи',

    ],

    'warnings' => [
        'delete-media' => 'сигурни ли сте, че искате да изтриете :filename?',
    ],

    'sentences' => [
        'select-image-to-view-info' => 'изберете файл, за да видите информацията за него.',
        'add-an-alt-text-to-this-image' => 'добавете алтернативен текст към този елемент.',
        'add-a-caption-to-this-image' => 'добавете надпис/описание към този елемент.',
        'enter-search-term' => 'въведете дума за търсене',
        'enter-folder-name' => 'въведете термин за папката',
        'folder-files' => '{0} Папката е празна|{1} 1 файл|[2,*] :count файлове',
    ],

    'media' => [
        'choose-image' => 'изберете изображение|изберете изображения',
        'no-image-selected-yet' => 'все още няма избран елемент.',
        'storing-files' => 'съхраняване на файлове...',
        'clear-image' => 'изчисти',
        'warning-unstored-uploads' => 'Не забравяйте да щракнете върху \'store\', за да качите файла си|Не забравяйте да щракнете върху \'store\', за да качите вашите файлове',
        'will-be-available-soon' => 'Вашата медия ще бъде достъпна скоро',

        'no-images-found' => [
            'title' => 'няма намерени изображения',
            'description' => 'започнете, като качите първия си елемент.',
        ],
    ],

    'components' => [
        'browse-library' => [
            'breadcrumbs' => [
                'root' => 'Медия библиотека',
            ],
            'modals' => [
                'create-media-folder' => [
                    'heading' => 'Създаване на папка',
                    'subheading' => 'Папката ще бъде създадена в текущата папка.',
                    'form' => [
                        'name' => [
                            'placeholder' => 'Име на папка',
                        ],
                    ],
                    'messages' => [
                        'created' => [
                            'body' => 'Папката е създадена',
                        ],
                    ],
                ],
                'rename-media-folder' => [
                    'heading' => 'Въведете ново име за тази папка',
                    'form' => [
                        'name' => [
                            'placeholder' => 'Име на папка',
                        ],
                    ],
                    'messages' => [
                        'renamed' => [
                            'body' => 'Папката е преименувана',
                        ],
                    ],
                ],
                'move-media-folder' => [
                    'heading' => 'Изберете ново местоположение за тази папка',
                    'subheading' => 'Всички елементи в папката също ще бъдат преместени.',
                    'form' => [
                        'media_library_folder_id' => [
                            'placeholder' => 'Изберете местоположение',
                        ],
                    ],
                    'messages' => [
                        'moved' => [
                            'body' => 'Папката е преместена',
                        ],
                    ],
                ],
                'delete-media-folder' => [
                    'heading' => 'Сигурни ли сте, че искате да изтриете тази папка?',
                    'subheading' => 'Всички файлове в папката няма да бъдат изтрити, а преместени в текущата папка.',
                    'form' => [
                        'fields' => [
                            'include_children' => [
                                'label' => 'Изтриване на цялото съдържание в папката',
                                'helper_text' => 'Внимание: това ще изтрие всички елементи в папката. Действието не може да бъде отменено.',
                            ],
                        ],
                    ],
                    'messages' => [
                        'deleted' => [
                            'body' => 'Папката е изтрита',
                        ],
                    ],
                ],
            ],
            'sort_order' => [
                'created_at_ascending' => 'Най-стари',
                'created_at_descending' => 'Най-нови',
                'name_ascending' => 'Име (A-Я)',
                'name_descending' => 'Име (Я-A)',
            ],
        ],
        'media-info' => [
            'heading' => 'Преглед на елемент',
            'move-media-item-form' => [
                'fields' => [
                    'media_library_folder_id' => [
                        'placeholder' => 'Изберете местоположение',
                    ],
                ],
                'messages' => [
                    'moved' => [
                        'body' => 'Елеменът е преместен',
                    ],
                ],
            ],
        ],
        'media-picker' => [
            'title' => 'медиа библиотека',
        ],
    ],

    'filament-tip-tap' => [
        'actions' => [
            'media-library-action' => [
                'modal-heading' => 'Изберете медия',
            ],
        ],
    ],
];
