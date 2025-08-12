<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GitHub Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for GitHub API integration
    |
    */

    'token' => env('GITHUB_TOKEN', null),
    
    /*
    |--------------------------------------------------------------------------
    | SSL Verification
    |--------------------------------------------------------------------------
    |
    | Whether to verify SSL certificates when making requests to GitHub API
    | Set to false for development environments with SSL issues
    |
    */
    'verify_ssl' => env('GITHUB_VERIFY_SSL', false),
    
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Base configurations for GitHub API
    |
    */
    'api' => [
        'base_url' => env('GITHUB_API_URL', 'https://api.github.com'),
        'version' => env('GITHUB_API_VERSION', '2022-11-28'),
        'timeout' => env('GITHUB_API_TIMEOUT', 30),
        'accept_format' => env('GITHUB_API_ACCEPT_FORMAT', 'application/vnd.github.v3+json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Repositories Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for repositories to fetch release notes from
    |
    */
    'repositories' => [
        'frontend' => [
            'owner' => env('GITHUB_FE_OWNER', 'ncc-erp'),
            'repo' => env('GITHUB_FE_REPO', 'ncc-erp-ams-fe'),
        ],
        'backend' => [
            'owner' => env('GITHUB_BE_OWNER', 'ncc-erp'),
            'repo' => env('GITHUB_BE_REPO', 'ncc-erp-ams'),
        ],
    ],
];
