<?php

return [
    'jobs' => [
        'single' => 'Trabajo En Cola',
        'plural' => 'Trabajos En Cola',
        'navigation_label' => 'Trabajos',
    ],
    'jobs_waiting' => [
        'single' => 'Trabajo en espera',
        'plural' => 'Trabajos en espera',
        'navigation_label' => 'Trabajos en espera',
    ],
    'jobs_failed' => [
        'single' => 'Trabajo no realizado',
        'plural' => 'Trabajos no realizados',
        'navigation_label' => 'Trabajos fallidos',
    ],
    'jobs_batches' => [
        'single' => 'Lote de tareas',
        'plural' => 'Lotes de trabajos',
        'navigation_label' => 'Lotes de trabajos',
    ],
    // general
    'breadcrumb' => 'Trabajo en Moox',
    'navigation_group' => 'Sistema',
    // used by multiple plugins
    'queue' => 'Cola',
    'name' => 'Nombre',
    'started_at' => 'Iniciado a las',
    'failed' => 'Fallido',
    'waiting' => 'En espera',
    'created_at' => 'Creado a las',
    'total_jobs' => 'Total Trabajos Ejecutados',
    'execution_time' => 'Tiempo Total de Ejecución',
    'average_time' => 'Tiempo Promedio de Ejecución',
    'succeeded' => 'Exitoso',
    'running' => 'En ejecución',
    'status' => 'Estado',
    'progress' => 'Progreso',
    'reserved_at' => 'Reservado a las',
    'waiting_jobs' => 'Número de trabajos en espera',
    'attempts' => 'Intentos',
    'failed_at' => 'Falló en',
    'delete' => 'Borrar',
    'finished_at' => 'Completado en',
    'exception_message' => 'Mensaje de excepción',
    // jobfailed
    'uuid' => 'Identificador único universal (Uuid)',
    'payload' => 'Cola',
    'connection' => 'Conexión',
    'exception' => 'Excepción',
    'retry' => 'Reintentar',
    'delete_all_failed_jobs' => 'Se eliminaron todos los trabajos fallidos',
    'delete_all_failed_jobs_notification' => 'Se han eliminado todos los trabajos fallidos.',
    'jobs_pushed_back_notification' => 'Los trabajos han sido devueltos a la cola.',
    // jobbatches
    'canceled_at' => 'Cancelado en',
    'failed_jobs' => 'Trabajos fallidos',
    'prune_batches_notification' => 'Todos los lotes han sido purgados.',
    // used by multiple plugins
    'id' => 'ID',
    'failed_job_id' => 'ID del trabajo fallido',
    'pending_jobs' => 'Trabajos pendientes',
    'prune_batches' => 'Purgar todos los lotes',
    'retry_all_failed_jobs' => 'Reintentar todos los trabajos fallidos',
    'retry_all_failed_jobs_notification' => 'Todos los trabajos fallidos se han puesto a la cola.',
    'job_pushed_back_notification' => 'está de vuelta en la cola.',
];
