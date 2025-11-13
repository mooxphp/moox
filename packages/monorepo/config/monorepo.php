<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GitHub Configuration
    |--------------------------------------------------------------------------
    */
    'github' => [
        'organization' => env('MONOREPO_GITHUB_ORG', 'mooxphp'),
        'public_repo' => env('MONOREPO_PUBLIC_REPO', 'moox'),
        'private_repo' => env('MONOREPO_PRIVATE_REPO', 'pro'),
        'default_branch' => env('MONOREPO_DEFAULT_BRANCH', 'main'),
        'api_version' => '2022-11-28',
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Configuration
    |--------------------------------------------------------------------------
    */
    'packages' => [
        'public_path' => env('MONOREPO_PUBLIC_PACKAGES_PATH', 'packages'),
        'private_path' => env('MONOREPO_PRIVATE_PACKAGES_PATH', null), // null means no private packages
        'devlog_path' => env('MONOREPO_DEVLOG_PATH', 'packages/monorepo/DEVLOG.md'),
        'stability_mapping' => [
            'init' => 'Initial release',
            'dev' => 'Compatibility release',
            'stable' => 'Compatibility release',
        ],
        // Automatically detect devlink paths if available
        'use_devlink_paths' => env('MONOREPO_USE_DEVLINK_PATHS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Release Configuration
    |--------------------------------------------------------------------------
    */
    'release' => [
        'auto_generate_notes' => env('MONOREPO_AUTO_GENERATE_NOTES', false),
        'workflow_file' => env('MONOREPO_WORKFLOW_FILE', 'split.yml'),
        'max_payload_size' => env('MONOREPO_MAX_PAYLOAD_SIZE', 50000),
        'batch_size' => env('MONOREPO_BATCH_SIZE', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Repository Creation Configuration
    |--------------------------------------------------------------------------
    */
    'repository' => [
        'default_license' => env('MONOREPO_DEFAULT_LICENSE', null),
        'auto_init' => env('MONOREPO_REPO_AUTO_INIT', false), // Keep empty for workflow
        'gitignore_template' => env('MONOREPO_REPO_GITIGNORE', null), // Let workflow handle
        'has_issues' => env('MONOREPO_REPO_HAS_ISSUES', true),
        'has_projects' => env('MONOREPO_REPO_HAS_PROJECTS', false),
        'has_wiki' => env('MONOREPO_REPO_HAS_WIKI', false),
        'has_discussions' => env('MONOREPO_REPO_HAS_DISCUSSIONS', false),
        'allow_forking' => env('MONOREPO_REPO_ALLOW_FORKING', true),
        'web_commit_signoff_required' => env('MONOREPO_REPO_WEB_COMMIT_SIGNOFF', false),
        'allow_squash_merge' => env('MONOREPO_REPO_ALLOW_SQUASH', true),
        'allow_merge_commit' => env('MONOREPO_REPO_ALLOW_MERGE', false),
        'allow_rebase_merge' => env('MONOREPO_REPO_ALLOW_REBASE', false),
        'allow_auto_merge' => env('MONOREPO_REPO_ALLOW_AUTO_MERGE', false),
        'delete_branch_on_merge' => env('MONOREPO_REPO_DELETE_BRANCH_ON_MERGE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('MONOREPO_CACHE_ENABLED', true),
        'ttl' => env('MONOREPO_CACHE_TTL', 300), // 5 minutes
        'prefix' => 'monorepo_v2',
    ],
];
