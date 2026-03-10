<?php

return [
    // Sync refers to the package name Moox Sync, and data transfer between platforms
    'sync' => 'Синхронизация',
    // Syncs refers to the package name Moox Sync, and data transfer between platforms
    'syncs' => 'Синхронизации',
    // Platform refers to the platform that data is being transferred from or to
    'platform' => 'Платформа',
    // Platforms refers to the platforms that data is being transferred from or to
    'platforms' => 'Платформы',
    // Source Platform refers to the platform that the data is being transferred from
    'source_platform' => 'Исходная платформа',
    // Target Platform refers to the platform that the data is being transferred to
    'target_platform' => 'Целевая платформа',
    // Target Platforms refers to the platform that the data is being transferred to
    'target_platforms' => 'Целевые платформы',
    // Source Platform refers to the platform that data is being transferred from
    'source_platform_id' => 'ID исходной платформы',
    // Target Platform refers to the platform that data is being transferred to
    'target_platform_id' => 'ID целевой платформы',
    // Sync error refers to any error that occurs in the package Moox Sync
    'sync_error' => 'Ошибка синхронизации',
    // Sync error: if someone tries to make multiple platforms the master platform
    'sync_error_master' => 'Может быть только одна главная платформа.',
    // Sync error: if someone tries to make the same platform and model the source and target
    'sync_error_platforms' => 'Исходная и целевая платформы не могут совпадать с одной и той же моделью.',
    // The source model refers to the model that data is being transferred from
    'source_model' => 'Исходная модель',
    // The target model refers to the model that data is being transferred to
    'target_model' => 'Целевая модель',
    // Use relations from the model to platform
    'use_platform_relations' => 'Использовать связи платформ',
    // If exists refers to the action to take if the record already exists
    'if_exists' => 'Если существует',
    // Filter ids refers to the ids to filter when syncing
    'filter_ids' => 'Фильтрация идентификаторов',
    // Ignore ids refers to the ids to ignore when syncing
    'ignore_ids' => 'Игнорировать идентификаторы',
    // Sync all records refers to syncing all records
    'sync_all_records' => 'Синхронизация всех записей',
    // Sync only ids refers to syncing only the ids
    'sync_only_ids' => 'Синхронизация только идентификаторов',
    // Sync all fields refers to syncing all fields
    'sync_all_fields' => 'Синхронизация всех полей',
    // Sync only fields defined in the field mappings
    'field_mappings' => 'Сопоставления полей',
    // Use a transformer class to transform the data when syncing
    'use_transformer_class' => 'Использовать класс Transformer',
    // When was the last time the data was synced
    'last_sync' => 'Последняя синхронизация',
    // Source Platform and Model refers to the source of the data being transferred
    'source_platform_and_model' => 'Исходная платформа и модель',
    // Target Platform and Model refers to the target of the data being transferred
    'target_platform_and_model' => 'Целевая платформа и модель',
    // Syncable ID refers to the ID of the record being synced
    'syncable_id' => 'Синхронизируемый идентификатор',
    // Syncable Type refers to the type of record being synced
    'syncable_type' => 'Синхронизируемый тип',
    // API Token refers to the token used to authenticate the API
    'api_token' => 'Токен API',
    // Generate Token refers to the action to generate a new token
    'generate_token' => 'Сгенерировать токен',
    // Locked means the platform or model is locked and cannot be synced
    'locked' => 'Заблокировано',
    // Lock Reason refers to the reason the platform or model is locked
    'lock_reason' => 'Причина блокировки',
    // Model Compatibility Warning
    'model_compatibility_warning' => 'Предупреждение о совместимости модели',
    // The selected models are not fully compatible
    'models_are_not_fully_compatible' => 'Выбранные модели не полностью совместимы',
    // Missing columns
    'missing_columns' => 'Отсутствующие столбцы',
    // Extra columns
    'extra_columns' => 'Лишние столбцы',
    // Please map fields manually
    'please_map_fields_manually' => 'Сопоставьте поля вручную',
];
