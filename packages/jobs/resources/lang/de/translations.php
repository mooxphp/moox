<?php

return [
    'jobs' => [
        'single' => 'Job',
        'plural' => 'Jobs',
        'navigation_label' => 'Jobs',
    ],
    'jobs_waiting' => [
        'single' => 'Job waiting',
        'plural' => 'Jobs waiting',
        'navigation_label' => 'Jobs Waiting',
    ],
    'jobs_failed' => [
        'single' => 'Job Failed',
        'plural' => 'Jobs Failed',
        'navigation_label' => 'Jobs Failed',
    ],
    'jobs_batches' => [
        'single' => 'Job Batch',
        'plural' => 'Jobs Batches',
        'navigation_label' => 'Job Batches',
    ],

    //general
    'breadcrumb' => 'Jobs',
    'navigation_group' => 'Job Manager',

    //used by multiple plugins
    'id' => 'ID',
    'failed_at' => 'Fehlgeschlagen am',
    'delete' => 'Löschen',
    'queue' => 'Warteschlange',
    'name' => 'Name',
    'started_at' => 'Gestartet am',
    'finished_at' => 'Fertiggestellt am',
    'failed' => 'Fehlgeschlagen',
    'waiting' => 'Wartend',
    'exception_message' => 'Exception message',
    'created_at' => 'Erstellt am',

    //jobs
    'status' => 'Status',
    'running' => 'In Bearbeitung',
    'succeeded' => 'Erfolgreich',
    'progress' => 'Fortschritt',

    //Jobswaiting
    'attempts' => 'Versuche',
    'reserved_at' => 'Reserviert am',
    'waiting_jobs' => 'Wartende Jobs',
    'execution_time' => 'Gesamte Ausführungszeit',
    'average_time' => 'Durchschnittliche Ausführungszeit',

    //jobfailed
    'uuid' => 'Uuid',
    'payload' => 'Warteschlange',
    'connection' => 'Verbindung',
    'exception' => 'Ausnahme',
    'retry' => 'Wiederholen',
    'retry_all_failed_jobs' => 'Alle Jobs wiederholen',
    'retry_all_failed_jobs_notification' => 'Alle fehlgeschlagenen Jobs wurden in die Warteschlage eingereiht',
    'delete_all_failed_jobs' => 'Alle fehlgeschlagenen Jobs löschen',
    'delete_all_failed_jobs_notification' => 'Alle fehlgeschlagenen Jobs wurden gelöscht',
    'jobs_pushed_back_notification' => 'Jobs sind wieder in der Warteschlange.',
    'job_pushed_back_notification' => 'ist wieder zurück in der Warteschlange',

    //jobbatches
    'canceled_at' => 'Abgebrochen am',
    'failed_jobs' => 'Fehlgeschlagene Jobs',
    'failed_job_id' => 'Fehlgeschlagener Job ID',
    'pending_jobs' => 'Ausstehende Jobs',
    'total_jobs' => 'Anzahl ausgeführter Jobs',
    'prune_batches' => 'Prune alle Batches',
    'prune_batches_notification' => 'Alle Batches wurden gepruned',

];
