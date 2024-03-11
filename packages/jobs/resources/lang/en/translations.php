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
    'breadcrumb' => 'Job Manager',
    'navigation_group' => 'Job Manager',

    //used by multiple plugins
    'id' => 'ID',
    'failed_at' => 'Failed at',
    'delete' => 'Delete',
    'queue' => 'Queue',
    'name' => 'Name',
    'started_at' => 'Started at',
    'finished_at' => 'Finished at',
    'failed' => 'Failed',
    'waiting' => 'Waiting',
    'exception_message' => 'Exception message',
    'created_at' => 'Created at',

    //jobs
    'status' => 'Status',
    'running' => 'Running',
    'succeeded' => 'Succeeded',
    'progress' => 'Progress',

    //Jobswaiting
    'attempts' => 'Attempts',
    'reserved_at' => 'Reserved at',
    'waiting_jobs' => 'Total Jobs Waiting',
    'execution_time' => 'Total Execution Time',
    'average_time' => 'Average Execution Time',

    //jobfailed
    'uuid' => 'Uuid',
    'payload' => 'Queue',
    'connection' => 'Connection',
    'exception' => 'Exception',
    'retry' => 'Retry',
    'retry_all_failed_jobs' => 'Retry all failed Jobs',
    'retry_all_failed_jobs_notification' => 'All failed jobs have been pushed back onto the queue.',
    'delete_all_failed_jobs' => 'Deleted all failed Jobs',
    'delete_all_failed_jobs_notification' => 'All failed jobs have been removed.',
    'jobs_pushed_back_notification' => 'jobs have been pushed back onto the queue.',
    'job_pushed_back_notification' => 'has been pushed back onto the queue.',

    //jobbatches
    'canceled_at' => 'Canceled at',
    'failed_jobs' => 'Failed Jobs',
    'failed_job_id' => 'Failed Job id',
    'pending_jobs' => 'Pending Jobs',
    'total_jobs' => 'Total Jobs Executed',
    'prune_batches' => 'Prune all batches',
    'prune_batches_notification' => 'All batches have been pruned.',

];
