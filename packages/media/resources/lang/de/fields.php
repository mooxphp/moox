<?php

return [
    // General
    'media' => 'Medien',
    'file|files' => 'Datei|Dateien',
    'link|links' => 'Verknüpfung|Verknüpfungen',
    'no_title' => 'Kein Titel',
    'upload' => 'Datei hochladen',

    // File Information
    'mime_type' => 'Dateityp',
    'size' => 'Dateigröße',
    'file_name' => 'Originaldateiname',
    'dimensions' => 'Abmessungen',
    'created_at' => 'Hochgeladen am',
    'updated_at' => 'Zuletzt bearbeitet',
    'uploaded_by' => 'Hochgeladen von',
    'usage' => 'Verwendet in',
    'not_used' => 'Nicht verwendet',
    'uploaded_at' => 'Hochgeladen am',
    'collection' => 'Sammlung',
    'default_collection' => 'Allgemein',

    // Actions
    'select_multiple' => 'Mehrere auswählen',
    'end_selection' => 'Auswahl beenden',
    'delete_selected' => 'Ausgewählte löschen',
    'delete_file' => 'Datei löschen',
    'yes_delete' => 'Ja, löschen',
    'cancel' => 'Abbrechen',
    'download_file' => 'Datei herunterladen',

    // Notifications
    'delete_error' => 'Fehler beim Löschen',
    'delete_success' => 'Datei erfolgreich gelöscht',
    'protected_skipped' => 'Geschützte Dateien übersprungen',
    'delete_confirmation' => 'Sind Sie sicher, dass Sie die ausgewählten Medien löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.',
    'protected_file_error' => 'Diese Datei ist schreibgeschützt und kann nicht gelöscht werden.',
    'delete_file_error' => 'Die Datei ":fileName" konnte nicht gelöscht werden.',
    'delete_file_success' => 'Die Datei ":fileName" wurde erfolgreich gelöscht.',
    'delete_file_heading' => 'Datei ":title" löschen',
    'delete_file_description' => 'Sind Sie sicher, dass Sie diese Datei löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.',
    'protected_file_skipped|protected_files_skipped' => 'Geschützte Datei übersprungen|Geschützte Dateien übersprungen',
    'file_deleted|files_deleted' => 'Datei gelöscht|Dateien gelöscht',
    'file_could_not_be_deleted|files_could_not_be_deleted' => 'Datei konnte nicht gelöscht werden|Dateien konnten nicht gelöscht werden',
    'replace_file_success' => 'Die Datei ":oldFileName" wurde erfolgreich durch ":newFileName" ersetzt.',
    'replace_file_error' => 'Die Datei ":fileName" konnte nicht ersetzt werden.',
    'replace_error' => 'Fehler beim Ersetzen der Datei',

    // Linked Files
    'file_has_links' => 'Diese Datei wird in :count :links verwendet.',
    'delete_linked_file_heading' => 'Datei ":title" löschen',
    'warning_file_has_links' => 'Diese Datei wird in :count :links verwendet.',
    'delete_linked_warning' => 'Wenn Sie diese Datei löschen, werden die Verknüpfungen zu dieser Datei ebenfalls gelöscht.',
    'replace_file' => 'Datei ersetzen',

    // Upload
    'upload_file' => 'Datei hochladen',
    'select_file' => 'Datei auswählen',
    'file_uploaded_success' => 'Datei erfolgreich hochgeladen',
    'file_upload_error' => 'Fehler beim Hochladen der Datei',
    'edit_file_success' => 'Die Datei ":fileName" wurde erfolgreich aktualisiert.',
    'operation_error' => 'Fehler beim Vorgang',
    'file_operation_error' => 'Die Datei ":fileName" konnte nicht verarbeitet werden.',

    // Metadata
    'metadata' => 'Metadaten',
    'alt_text' => 'Alternativer Text',
    'internal_note' => 'Interne Notizen',
    'name' => 'Name',
    'title' => 'Titel',
    'description' => 'Beschreibung',

    // Date Filters
    'today' => 'Heute',
    'week' => 'Diese Woche',
    'month' => 'Diesen Monat',
    'year' => 'Dieses Jahr',

    // Collections
    'all_collections' => 'Alle Sammlungen',
    'collection_name' => 'Sammlungsname',
    'collection_description' => 'Sammlungsbeschreibung',
    'media_count' => 'Medienanzahl',
    'delete_collection' => 'Sammlung löschen',
    'delete_collection_heading' => 'Sammlung ":name" löschen',
    'delete_collection_with_media_heading' => 'Sammlung ":name" mit :count Dateien löschen',
    'delete_collection_warning' => 'Möchten Sie diese Sammlung wirklich löschen?',
    'delete_collection_with_media_warning' => 'Diese Sammlung enthält :count :files. Diese Dateien werden automatisch in die Sammlung ":uncategorized" verschoben.',
    'uncategorized' => 'Nicht zugeordnet',
    'uncategorized_description' => 'Nicht zugeordnete Medien',
    'extend_existing_collection' => 'Vorhandene Sammlung übersetzen',
    'create_new_collection' => 'Neue Sammlung',

    // Media Types
    'images' => 'Bilder',
    'videos' => 'Videos',
    'audios' => 'Audios',
    'documents' => 'Dokumente',

    // Errors
    'class_not_found' => 'Die Klasse ":class" existiert nicht.',
    'collection_name_already_exists' => 'Eine Sammlung mit diesem Namen existiert bereits.',

    // Media Picker
    'upload_and_select_media' => 'Hochladen und Medien auswählen',
    'no_media_selected' => 'Keine Medien ausgewählt.',
    'select_media' => 'Medien auswählen',
    'search' => 'Nach Medien suchen...',
    'all_types' => 'Alle Typen',
    'all_periods' => 'Alle Zeiträume',
    'edit_metadata' => 'Metadaten bearbeiten',
    'file_type' => 'Dateityp',
    'apply_selection' => 'Auswahl übernehmen',
    'close' => 'Schließen',

    // Duplicate Files
    'duplicate_file' => 'Doppelte Datei',
    'duplicate_file_message' => 'Die Datei ":fileName" existiert bereits in der Mediathek.',

    // View
    'grid_view' => 'Gitteransicht',
    'table_view' => 'Listenansicht',
];
